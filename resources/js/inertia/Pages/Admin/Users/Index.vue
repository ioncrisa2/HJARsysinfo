<script setup>
import { ref, watch, computed } from "vue";
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import AdminLayout from "../../../Layouts/AdminLayout.vue";
import Select from "primevue/select";
import { useConfirm } from "primevue/useconfirm";

const props = defineProps({
    users: Object,
    filters: Object,
    roles: Array,
    can: { type: Object, default: () => ({}) },
});

const confirm = useConfirm();
const search = ref(props.filters.search || "");
const role = ref(props.filters.role || null);
const status = ref(props.filters.status || null);
const selectedUsers = ref([]);

const statusOptions = [
    { label: "All Status", value: null },
    { label: "Active Only", value: "active" },
    { label: "Inactive Only", value: "inactive" },
];

const updateFilters = () => {
    router.get(
        "/admin/users",
        {
            search: search.value,
            role: role.value,
            status: status.value,
        },
        { preserveState: true, replace: true }
    );
};

let searchTimeout = null;
watch(search, (value) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(updateFilters, 300);
});

watch([role, status], updateFilters);

const deleteUser = (id) => {
    confirm.require({
        message: 'Are you sure you want to delete this user?',
        header: 'Delete Confirmation',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(`/admin/users/${id}`);
        }
    });
};

const bulkDelete = () => {
    confirm.require({
        message: `Are you sure you want to delete ${selectedUsers.value.length} users?`,
        header: 'Bulk Delete Confirmation',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.post('/admin/users/bulk-delete', { ids: selectedUsers.value }, {
                onSuccess: () => { selectedUsers.value = []; }
            });
        }
    });
};

const toggleAll = (event) => {
    if (event.target.checked) {
        selectedUsers.value = props.users.data.map(u => u.id);
    } else {
        selectedUsers.value = [];
    }
};

const isAllSelected = computed(() => {
    return props.users.data.length > 0 && selectedUsers.value.length === props.users.data.length;
});

const toggleStatus = (id) => {
    router.patch(`/admin/users/${id}/toggle-status`, {}, { preserveScroll: true });
};
</script>

<template>
    <AdminLayout title="User Management — Admin">
        <div class="mb-6 space-y-4">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <h2 class="text-xl font-bold text-slate-900">Users</h2>
                    <div v-if="props.can.deleteAny && selectedUsers.length > 0" class="flex items-center gap-2 animate-in fade-in slide-in-from-left-4 duration-300">
                        <span class="text-xs font-bold text-slate-500 bg-slate-100 px-2 py-1 rounded">
                            {{ selectedUsers.length }} Selected
                        </span>
                        <button 
                            @click="bulkDelete"
                            class="inline-flex items-center gap-1.5 bg-red-50 text-red-600 px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-red-100 transition border border-red-200"
                        >
                            <i class="pi pi-trash text-[10px]" />
                            Bulk Delete
                        </button>
                    </div>
                </div>
                <Link
                    v-if="props.can.create"
                    href="/admin/users/create"
                    class="inline-flex items-center justify-center gap-2 bg-slate-900 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-800 transition shadow-sm"
                >
                    <i class="pi pi-plus text-xs" />
                    Create New User
                </Link>
            </div>

            <!-- Filters Bar -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <div class="relative">
                    <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search name or email..."
                        class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors bg-slate-50/50"
                    />
                </div>

                <Select
                    v-model="role"
                    :options="[{ label: 'All Roles', value: null }, ...roles]"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Filter by Role"
                    class="w-full text-sm"
                />

                <Select
                    v-model="status"
                    :options="statusOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Filter by Status"
                    class="w-full text-sm"
                />
                
                <button 
                    v-if="search || role || status"
                    @click="search = ''; role = null; status = null"
                    class="text-sm text-slate-500 hover:text-slate-800 font-medium transition-colors"
                >
                    Reset Filters
                </button>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50/50 border-b border-slate-200 text-slate-600 font-semibold uppercase tracking-wider text-[10px]">
                        <tr>
                            <th v-if="props.can.deleteAny" class="px-6 py-4 w-10">
                                <input 
                                    type="checkbox" 
                                    :checked="isAllSelected"
                                    @change="toggleAll"
                                    class="rounded border-slate-300 text-slate-900 focus:ring-slate-900"
                                />
                            </th>
                            <th class="px-6 py-4">User Details</th>
                            <th class="px-6 py-4">Roles</th>
                            <th class="px-6 py-4">Status</th>
                            <th v-if="props.can.update || props.can.delete" class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr 
                            v-for="user in users.data" 
                            :key="user.id" 
                            class="hover:bg-slate-50/50 transition-colors"
                            :class="selectedUsers.includes(user.id) ? 'bg-slate-50' : ''"
                        >
                            <td v-if="props.can.deleteAny" class="px-6 py-4 w-10">
                                <input 
                                    type="checkbox" 
                                    v-model="selectedUsers"
                                    :value="user.id"
                                    class="rounded border-slate-300 text-slate-900 focus:ring-slate-900"
                                />
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 font-bold border border-slate-200">
                                        {{ user.name.slice(0, 1).toUpperCase() }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900 leading-tight">{{ user.name }}</p>
                                        <p class="text-xs text-slate-500">{{ user.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    <span 
                                        v-for="userRole in user.roles" 
                                        :key="userRole.id"
                                        class="px-2 py-0.5 rounded bg-blue-50 text-blue-600 text-[10px] font-bold uppercase tracking-tight border border-blue-100"
                                    >
                                        {{ userRole.name.replace('_', ' ') }}
                                    </span>
                                    <span v-if="!user.roles.length" class="text-xs text-slate-400 italic">No roles</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <button
                                    v-if="props.can.update"
                                    @click="toggleStatus(user.id)"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider transition-all"
                                    :class="!user.deactivated_at 
                                        ? 'bg-emerald-50 text-emerald-600 border border-emerald-100 hover:bg-emerald-100' 
                                        : 'bg-red-50 text-red-600 border border-red-100 hover:bg-red-100'"
                                    :title="!user.deactivated_at ? 'Click to Deactivate' : 'Click to Activate'"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full" :class="!user.deactivated_at ? 'bg-emerald-500' : 'bg-red-500'"></span>
                                    {{ !user.deactivated_at ? 'Active' : 'Inactive' }}
                                </button>
                            </td>
                            <td v-if="props.can.update || props.can.delete" class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Link
                                        v-if="props.can.update"
                                        :href="`/admin/users/${user.id}/edit`"
                                        class="p-2 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors"
                                        title="Edit User"
                                    >
                                        <i class="pi pi-pencil text-xs" />
                                    </Link>
                                    <button
                                        v-if="props.can.delete"
                                        @click="deleteUser(user.id)"
                                        class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                        title="Delete User"
                                    >
                                        <i class="pi pi-trash text-xs" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="users.data.length === 0">
                            <td colspan="4" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <i class="pi pi-users text-4xl mb-3 opacity-20" />
                                    <p class="font-medium">No users found matching your filters.</p>
                                    <button @click="search = ''; role = null; status = null" class="mt-2 text-blue-500 text-sm hover:underline">Clear all filters</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div v-if="users.links.length > 3" class="px-6 py-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                <span class="text-xs font-medium text-slate-500">
                    Showing {{ users.from }} to {{ users.to }} of {{ users.total }} users
                </span>
                <div class="flex gap-1">
                    <template v-for="(link, i) in users.links" :key="i">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            class="px-3 py-1.5 text-xs rounded-lg transition-all font-medium"
                            :class="[
                                link.active ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100',
                            ]"
                        />
                        <span
                            v-else
                            v-html="link.label"
                            class="px-3 py-1.5 text-xs rounded-lg font-medium opacity-30 cursor-not-allowed"
                            :class="[
                                link.active ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600',
                            ]"
                        />
                    </template>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
