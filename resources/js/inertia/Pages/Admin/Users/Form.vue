<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import AdminLayout from "../../../Layouts/AdminLayout.vue";
import ToggleSwitch from 'primevue/toggleswitch';

const props = defineProps({
    user: Object,
    roles: Array,
    userRoles: Array,
});

const isEditing = !!props.user.id;

const form = useForm({
    name: props.user.name || "",
    email: props.user.email || "",
    password: "",
    is_active: props.user.deactivated_at ? false : true,
    roles: props.userRoles || [],
});

const submit = () => {
    if (isEditing) {
        form.put(`/admin/users/${props.user.id}`);
    } else {
        form.post("/admin/users");
    }
};

const toggleRole = (roleName) => {
    const index = form.roles.indexOf(roleName);
    if (index === -1) {
        form.roles.push(roleName);
    } else {
        form.roles.splice(index, 1);
    }
};
</script>

<template>
    <AdminLayout :title="(isEditing ? 'Edit User' : 'Create User') + ' — Admin'">

        <div class="mb-4">
            <Link href="/admin/users" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-800 transition">
                <i class="pi pi-arrow-left text-[10px]" />
                Back to Users
            </Link>
        </div>

        <div class="max-w-4xl">
            <form @submit.prevent="submit" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-8 space-y-8">
                    
                    <div class="flex items-center gap-4 mb-2">
                        <div class="h-12 w-12 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 border border-slate-200">
                            <i class="pi pi-user text-xl" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">{{ isEditing ? 'Update Profile' : 'New User Account' }}</h2>
                            <p class="text-xs text-slate-500">Configure access and basic information for the system user.</p>
                        </div>
                    </div>

                    <!-- Basic Information -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div class="md:col-span-1">
                            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-1">Basic Details</h3>
                            <p class="text-xs text-slate-500">Personal information and login credentials.</p>
                        </div>
                        
                        <div class="md:col-span-2 space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-tight mb-1.5">Full Name</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="e.g. John Doe"
                                    class="w-full px-4 py-2.5 border rounded-xl text-sm transition focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 bg-slate-50/50"
                                    :class="form.errors.name ? 'border-red-400' : 'border-slate-200'"
                                />
                                <p v-if="form.errors.name" class="mt-1.5 text-xs text-red-500 flex items-center gap-1"><i class="pi pi-exclamation-circle text-[10px]" /> {{ form.errors.name }}</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-tight mb-1.5">Email Address</label>
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        required
                                        placeholder="john@example.com"
                                        class="w-full px-4 py-2.5 border rounded-xl text-sm transition focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 bg-slate-50/50"
                                        :class="form.errors.email ? 'border-red-400' : 'border-slate-200'"
                                    />
                                    <p v-if="form.errors.email" class="mt-1.5 text-xs text-red-500 flex items-center gap-1"><i class="pi pi-exclamation-circle text-[10px]" /> {{ form.errors.email }}</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-tight mb-1.5">
                                        Password <span v-if="isEditing" class="text-slate-400 font-normal capitalize">(Empty to skip)</span>
                                    </label>
                                    <input
                                        v-model="form.password"
                                        type="password"
                                        :required="!isEditing"
                                        placeholder="••••••••"
                                        class="w-full px-4 py-2.5 border rounded-xl text-sm transition focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 bg-slate-50/50"
                                        :class="form.errors.password ? 'border-red-400' : 'border-slate-200'"
                                    />
                                    <p v-if="form.errors.password" class="mt-1.5 text-xs text-red-500 flex items-center gap-1"><i class="pi pi-exclamation-circle text-[10px]" /> {{ form.errors.password }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-slate-100"></div>

                    <!-- Authorization & Status -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div class="md:col-span-1">
                            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-1">Access Control</h3>
                            <p class="text-xs text-slate-500">Define user roles and current account status.</p>
                        </div>
                        
                        <div class="md:col-span-2 space-y-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-tight mb-3">Assigned Roles</label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <label 
                                        v-for="role in roles" 
                                        :key="role.id" 
                                        class="flex items-center justify-between p-3 rounded-xl border cursor-pointer transition-all"
                                        :class="form.roles.includes(role.name) ? 'border-blue-500 bg-blue-50/30' : 'border-slate-200 hover:bg-slate-50'"
                                    >
                                        <div class="flex items-center gap-3">
                                            <div class="h-8 w-8 rounded-lg flex items-center justify-center border" :class="form.roles.includes(role.name) ? 'bg-blue-500 border-blue-500 text-white' : 'bg-slate-100 border-slate-200 text-slate-400'">
                                                <i class="pi pi-shield text-[10px]" />
                                            </div>
                                            <span class="text-sm font-semibold" :class="form.roles.includes(role.name) ? 'text-blue-900' : 'text-slate-700'">
                                                {{ role.name.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
                                            </span>
                                        </div>
                                        <input
                                            type="checkbox"
                                            :checked="form.roles.includes(role.name)"
                                            @change="toggleRole(role.name)"
                                            class="hidden"
                                        />
                                        <div v-if="form.roles.includes(role.name)" class="text-blue-500">
                                            <i class="pi pi-check-circle" />
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50/50 flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-bold text-slate-800">Account Active Status</p>
                                    <p class="text-xs text-slate-500">Toggle to enable or disable access immediately.</p>
                                </div>
                                <ToggleSwitch v-model="form.is_active" />
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Footer Actions -->
                <div class="px-8 py-4 bg-slate-50 flex items-center justify-end gap-3 border-t border-slate-100">
                    <Link
                        href="/admin/users"
                        class="px-5 py-2.5 rounded-xl text-sm font-bold text-slate-600 hover:bg-slate-200 transition"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 rounded-xl text-sm font-bold text-white bg-slate-900 hover:bg-slate-800 transition disabled:opacity-50 flex items-center gap-2 shadow-sm shadow-slate-200"
                    >
                        <i v-if="form.processing" class="pi pi-spin pi-spinner text-xs" />
                        <i v-else class="pi pi-check text-[10px]" />
                        {{ isEditing ? 'Save Changes' : 'Create User' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

