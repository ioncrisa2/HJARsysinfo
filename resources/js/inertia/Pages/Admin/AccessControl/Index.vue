<script setup>
import { computed, ref } from "vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import AdminLayout from "../../../Layouts/AdminLayout.vue";
import { useConfirm } from "primevue/useconfirm";
import Dialog from "primevue/dialog";

const props = defineProps({
    roles: Array,
    permissions: Array,
    metrics: Object,
    can: { type: Object, default: () => ({}) },
});

const confirm = useConfirm();
const activeTab = ref("roles");
const permissionSearch = ref("");
const editingRoleId = ref(null);
const roleDialogVisible = ref(false);
const permissionDialogVisible = ref(false);

const roleForm = useForm({
    name: "",
    permissions: [],
});

const permissionForm = useForm({
    name: "",
});

const filteredPermissions = computed(() => {
    const term = permissionSearch.value.trim().toLowerCase();

    if (!term) {
        return props.permissions;
    }

    return props.permissions.filter((permission) => {
        return permission.name.toLowerCase().includes(term)
            || permission.group.toLowerCase().includes(term);
    });
});

const groupedPermissions = computed(() => {
    return filteredPermissions.value.reduce((groups, permission) => {
        const group = permission.group || "General";
        groups[group] = groups[group] || [];
        groups[group].push(permission);

        return groups;
    }, {});
});

const isEditingRole = computed(() => editingRoleId.value !== null);

const selectedRole = computed(() => {
    if (!editingRoleId.value) {
        return null;
    }

    return props.roles.find((role) => role.id === editingRoleId.value) ?? null;
});

const resetRoleForm = () => {
    editingRoleId.value = null;
    permissionSearch.value = "";
    roleForm.reset();
    roleForm.clearErrors();
};

const openCreateRole = () => {
    if (!props.can.createRole) return;

    resetRoleForm();
    activeTab.value = "roles";
    roleDialogVisible.value = true;
};

const editRole = (role) => {
    if (!props.can.updateRole) return;

    editingRoleId.value = role.id;
    roleForm.name = role.name;
    roleForm.permissions = [...role.permissions];
    activeTab.value = "roles";
    roleDialogVisible.value = true;
};

const closeRoleDialog = () => {
    roleDialogVisible.value = false;
    resetRoleForm();
};

const togglePermission = (permissionName) => {
    const index = roleForm.permissions.indexOf(permissionName);

    if (index === -1) {
        roleForm.permissions.push(permissionName);
    } else {
        roleForm.permissions.splice(index, 1);
    }
};

const submitRole = () => {
    if ((isEditingRole.value && !props.can.updateRole) || (!isEditingRole.value && !props.can.createRole)) return;

    if (isEditingRole.value) {
        roleForm.put(`/admin/access-control/roles/${editingRoleId.value}`, {
            preserveScroll: true,
            onSuccess: closeRoleDialog,
        });

        return;
    }

    roleForm.post("/admin/access-control/roles", {
        preserveScroll: true,
        onSuccess: closeRoleDialog,
    });
};

const deleteRole = (role) => {
    if (!props.can.deleteRole) return;

    confirm.require({
        message: `Hapus role "${role.name}"?`,
        header: "Konfirmasi hapus role",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: () => router.delete(`/admin/access-control/roles/${role.id}`, { preserveScroll: true }),
    });
};

const submitPermission = () => {
    if (!props.can.createPermission) return;

    permissionForm.post("/admin/access-control/permissions", {
        preserveScroll: true,
        onSuccess: () => {
            permissionForm.reset();
            permissionDialogVisible.value = false;
            activeTab.value = "permissions";
        },
    });
};

const openCreatePermission = () => {
    if (!props.can.createPermission) return;

    permissionForm.reset();
    permissionForm.clearErrors();
    activeTab.value = "permissions";
    permissionDialogVisible.value = true;
};

const closePermissionDialog = () => {
    permissionDialogVisible.value = false;
    permissionForm.reset();
    permissionForm.clearErrors();
};

const deletePermission = (permission) => {
    if (!props.can.deletePermission) return;

    confirm.require({
        message: `Hapus permission "${permission.name}"?`,
        header: "Konfirmasi hapus permission",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: () => router.delete(`/admin/access-control/permissions/${permission.id}`, { preserveScroll: true }),
    });
};

const formatName = (value) => value.replace(/[_:-]+/g, " ").replace(/\b\w/g, (letter) => letter.toUpperCase());
</script>

<template>
    <AdminLayout title="Access Control - Admin">
        <div class="mb-6 space-y-4">
            <Link href="/admin/users" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-800">
                <i class="pi pi-arrow-left text-[10px]" />
                Users Management
            </Link>

            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Access Control</h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Kelola role dan permission yang dipakai untuk akses user.
                    </p>
                </div>

                <div class="grid grid-cols-3 gap-2 sm:w-[420px]">
                    <div class="rounded-lg border border-slate-200 bg-white p-3">
                        <p class="text-[11px] font-semibold uppercase text-slate-500">Roles</p>
                        <p class="mt-1 text-xl font-bold tabular-nums text-slate-900">{{ metrics.roles }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3">
                        <p class="text-[11px] font-semibold uppercase text-slate-500">Permissions</p>
                        <p class="mt-1 text-xl font-bold tabular-nums text-slate-900">{{ metrics.permissions }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3">
                        <p class="text-[11px] font-semibold uppercase text-slate-500">Assigned</p>
                        <p class="mt-1 text-xl font-bold tabular-nums text-slate-900">{{ metrics.assigned_permissions }}</p>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="inline-flex rounded-lg border border-slate-200 bg-white p-1">
                    <button
                        v-if="props.can.createPermission"
                        type="button"
                        class="rounded-md px-4 py-2 text-sm font-semibold transition-colors"
                        :class="activeTab === 'roles' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-50'"
                        @click="activeTab = 'roles'"
                    >
                        Roles
                    </button>
                    <button
                        v-if="props.can.createRole"
                        type="button"
                        class="rounded-md px-4 py-2 text-sm font-semibold transition-colors"
                        :class="activeTab === 'permissions' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-50'"
                        @click="activeTab = 'permissions'"
                    >
                        Permissions
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        @click="openCreatePermission"
                    >
                        <i class="pi pi-key text-xs" />
                        Permission Baru
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                        @click="openCreateRole"
                    >
                        <i class="pi pi-shield text-xs" />
                        Role Baru
                    </button>
                </div>
            </div>
        </div>

        <section v-if="activeTab === 'roles'">
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-sm font-bold text-slate-900">Role List</h2>
                    <p class="mt-1 text-xs text-slate-500">Role dipakai saat membuat atau mengubah user.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full whitespace-nowrap text-left text-sm">
                        <thead class="border-b border-slate-200 bg-slate-50 text-[11px] font-semibold uppercase text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Role</th>
                                <th class="px-5 py-3">Users</th>
                                <th class="px-5 py-3">Permissions</th>
                                <th class="px-5 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="role in roles" :key="role.id" class="hover:bg-slate-50/70">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="flex size-9 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                                            <i class="pi pi-shield text-sm" />
                                        </span>
                                        <div>
                                            <p class="font-semibold text-slate-900">{{ formatName(role.name) }}</p>
                                            <p class="text-xs text-slate-500">{{ role.name }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 tabular-nums text-slate-700">{{ role.users_count }}</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                        {{ role.permissions_count }} permissions
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            v-if="props.can.updateRole"
                                            type="button"
                                            class="inline-flex size-9 items-center justify-center rounded-lg text-slate-500 hover:bg-amber-50 hover:text-amber-700"
                                            :aria-label="`Edit role ${role.name}`"
                                            @click="editRole(role)"
                                        >
                                            <i class="pi pi-pencil text-xs" />
                                        </button>
                                        <button
                                            v-if="props.can.deleteRole"
                                            type="button"
                                            class="inline-flex size-9 items-center justify-center rounded-lg text-slate-500 hover:bg-red-50 hover:text-red-600 disabled:cursor-not-allowed disabled:opacity-40"
                                            :disabled="role.is_locked || role.users_count > 0"
                                            :aria-label="`Delete role ${role.name}`"
                                            @click="deleteRole(role)"
                                        >
                                            <i class="pi pi-trash text-xs" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="roles.length === 0">
                                <td colspan="4" class="px-5 py-12 text-center text-sm text-slate-500">
                                    Belum ada role.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section v-else>
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-sm font-bold text-slate-900">Permission List</h2>
                    <p class="mt-1 text-xs text-slate-500">Permission adalah kontrak akses yang dipakai route, policy, dan role.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full whitespace-nowrap text-left text-sm">
                        <thead class="border-b border-slate-200 bg-slate-50 text-[11px] font-semibold uppercase text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Permission</th>
                                <th class="px-5 py-3">Group</th>
                                <th class="px-5 py-3">Used By</th>
                                <th class="px-5 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="permission in permissions" :key="permission.id" class="hover:bg-slate-50/70">
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-slate-900">{{ permission.name }}</p>
                                    <p class="text-xs text-slate-500">{{ permission.guard_name }}</p>
                                </td>
                                <td class="px-5 py-4 text-slate-700">{{ permission.group }}</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                        {{ permission.roles_count }} roles / {{ permission.users_count }} users
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <button
                                        v-if="props.can.deletePermission"
                                        type="button"
                                        class="inline-flex size-9 items-center justify-center rounded-lg text-slate-500 hover:bg-red-50 hover:text-red-600 disabled:cursor-not-allowed disabled:opacity-40"
                                        :disabled="permission.is_locked || permission.roles_count > 0 || permission.users_count > 0"
                                        :aria-label="`Delete permission ${permission.name}`"
                                        @click="deletePermission(permission)"
                                    >
                                        <i class="pi pi-trash text-xs" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="permissions.length === 0">
                                <td colspan="4" class="px-5 py-12 text-center text-sm text-slate-500">
                                    Belum ada permission.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <Dialog
            v-model:visible="roleDialogVisible"
            modal
            :header="isEditingRole ? 'Edit Role' : 'Create Role'"
            class="w-[min(920px,calc(100vw-2rem))]"
            @hide="resetRoleForm"
        >
            <form class="space-y-5" @submit.prevent="submitRole">
                <div>
                    <label for="role_name" class="mb-1.5 block text-xs font-bold uppercase text-slate-700">Role name</label>
                    <input
                        id="role_name"
                        v-model="roleForm.name"
                        type="text"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 disabled:bg-slate-100"
                        :class="roleForm.errors.name ? 'border-red-400' : 'border-slate-200'"
                        :disabled="selectedRole?.is_locked"
                        placeholder="finance_admin"
                    />
                    <p v-if="roleForm.errors.name" class="mt-1.5 text-xs text-red-500">{{ roleForm.errors.name }}</p>
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <label for="permission_search" class="text-xs font-bold uppercase text-slate-700">Permissions</label>
                        <span class="text-xs font-medium text-slate-500">{{ roleForm.permissions.length }} selected</span>
                    </div>
                    <div class="relative">
                        <i class="pi pi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400" />
                        <input
                            id="permission_search"
                            v-model="permissionSearch"
                            type="search"
                            class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                            placeholder="Cari permission"
                        />
                    </div>
                </div>

                <div class="max-h-[52vh] space-y-4 overflow-y-auto rounded-lg border border-slate-100 bg-slate-50/50 p-3">
                    <div v-for="(items, group) in groupedPermissions" :key="group" class="space-y-2">
                        <p class="text-[11px] font-bold uppercase text-slate-500">{{ group }}</p>
                        <label
                            v-for="permission in items"
                            :key="permission.id"
                            class="flex cursor-pointer items-start gap-3 rounded-lg border bg-white p-3 transition-colors"
                            :class="roleForm.permissions.includes(permission.name) ? 'border-amber-300 bg-amber-50/60' : 'border-slate-200 hover:bg-slate-50'"
                        >
                            <input
                                type="checkbox"
                                class="mt-0.5 rounded border-slate-300 text-amber-600 focus:ring-amber-500"
                                :checked="roleForm.permissions.includes(permission.name)"
                                @change="togglePermission(permission.name)"
                            />
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-slate-800">{{ permission.name }}</span>
                                <span class="mt-0.5 block text-xs text-slate-500">
                                    {{ permission.roles_count }} roles
                                </span>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-4">
                    <button
                        type="button"
                        class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100"
                        :disabled="roleForm.processing"
                        @click="closeRoleDialog"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="roleForm.processing"
                        class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-60"
                    >
                        <i v-if="roleForm.processing" class="pi pi-spin pi-spinner text-xs" />
                        <i v-else class="pi pi-check text-xs" />
                        {{ isEditingRole ? 'Save Role' : 'Create Role' }}
                    </button>
                </div>
            </form>
        </Dialog>

        <Dialog
            v-model:visible="permissionDialogVisible"
            modal
            header="Create Permission"
            class="w-[min(460px,calc(100vw-2rem))]"
            @hide="permissionForm.clearErrors()"
        >
            <form class="space-y-4" @submit.prevent="submitPermission">
                <p class="text-sm text-slate-500">Gunakan nama stabil karena permission bisa dipakai middleware, policy, dan route.</p>

                <div>
                    <label for="permission_name" class="mb-1.5 block text-xs font-bold uppercase text-slate-700">Permission name</label>
                    <input
                        id="permission_name"
                        v-model="permissionForm.name"
                        type="text"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                        :class="permissionForm.errors.name ? 'border-red-400' : 'border-slate-200'"
                        placeholder="manage_reports"
                    />
                    <p v-if="permissionForm.errors.name" class="mt-1.5 text-xs text-red-500">{{ permissionForm.errors.name }}</p>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-4">
                    <button
                        type="button"
                        class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100"
                        :disabled="permissionForm.processing"
                        @click="closePermissionDialog"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="permissionForm.processing"
                        class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-60"
                    >
                        <i v-if="permissionForm.processing" class="pi pi-spin pi-spinner text-xs" />
                        <i v-else class="pi pi-plus text-xs" />
                        Create Permission
                    </button>
                </div>
            </form>
        </Dialog>
    </AdminLayout>
</template>
