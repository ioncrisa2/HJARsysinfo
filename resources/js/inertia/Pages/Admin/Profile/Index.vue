<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import AdminLayout from "../../../Layouts/AdminLayout.vue";
import UiEmptyState from "../../../components/ui/UiEmptyState.vue";
import UiField from "../../../components/ui/UiField.vue";
import UiSurface from "../../../components/ui/UiSurface.vue";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Password from "primevue/password";
import Tag from "primevue/tag";

const props = defineProps({
    user: { type: Object, default: () => ({}) },
    activities: { type: Array, default: () => [] },
});

const profileForm = useForm({
    name: props.user?.name ?? "",
    email: props.user?.email ?? "",
});

const passwordForm = useForm({
    current_password: "",
    password: "",
    password_confirmation: "",
});

const initials = (name) => {
    return (name ?? "A")
        .split(" ")
        .slice(0, 2)
        .map((word) => word[0])
        .join("")
        .toUpperCase();
};

const updateProfile = () => {
    profileForm.put("/admin/profile", {
        preserveScroll: true,
    });
};

const updatePassword = () => {
    passwordForm.put("/admin/profile/password", {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
};

const resetProfile = () => {
    profileForm.name = props.user?.name ?? "";
    profileForm.email = props.user?.email ?? "";
    profileForm.clearErrors();
};

const formatDateTime = (value) => {
    if (!value) return "-";

    return new Date(value.replace(" ", "T")).toLocaleString("id-ID", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};
</script>

<template>
    <AdminLayout title="Admin Profile">
        <Head title="Admin Profile" />

        <div class="mb-5">
            <h1 class="text-balance text-2xl font-black text-slate-900">Admin Profile</h1>
            <p class="mt-1 text-pretty text-sm text-slate-500">
                Perbarui identitas admin, password, dan tinjau aktivitas akun terbaru.
            </p>
        </div>

        <div class="grid gap-6 xl:grid-cols-[340px_1fr]">
            <aside class="space-y-5">
                <UiSurface padding="none" class="overflow-hidden">
                    <div class="border-b border-slate-100 bg-slate-50 px-4 py-4">
                        <p class="text-sm font-bold text-slate-900">Ringkasan Akun</p>
                        <p class="mt-1 text-xs text-slate-500">Identitas dan akses admin aktif.</p>
                    </div>

                    <div class="p-4">
                        <div class="flex items-start gap-3">
                            <div class="flex size-14 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-lg font-black text-slate-900">
                                {{ initials(user.name) }}
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-slate-900">{{ user.name }}</p>
                                <p class="truncate text-sm text-slate-500">{{ user.email }}</p>
                                <p class="ui-tabular mt-1 text-xs font-medium text-slate-500">
                                    Joined {{ formatDateTime(user.created_at) }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 border-t border-slate-100 pt-4">
                            <p class="text-xs font-semibold text-slate-600">Roles</p>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <Tag v-for="role in user.roles ?? []" :key="role" :value="role" severity="info" />
                                <span v-if="(user.roles ?? []).length === 0" class="text-xs text-slate-500">Tidak ada role.</span>
                            </div>
                        </div>
                    </div>
                </UiSurface>

                <UiSurface padding="none" class="overflow-hidden">
                    <div class="border-b border-slate-100 bg-slate-50 px-4 py-4">
                        <p class="text-sm font-bold text-slate-900">Permissions</p>
                        <p class="mt-1 text-xs text-slate-500">Hak akses efektif dari role admin.</p>
                    </div>

                    <div class="max-h-80 overflow-y-auto p-4">
                        <div v-if="(user.permissions ?? []).length" class="flex flex-wrap gap-1.5">
                            <Tag v-for="permission in user.permissions" :key="permission" :value="permission" severity="secondary" />
                        </div>
                        <UiEmptyState
                            v-else
                            title="Tidak ada permission"
                            description="Permission akan muncul setelah role dikonfigurasi."
                            icon="pi pi-key"
                        />
                    </div>
                </UiSurface>
            </aside>

            <section class="space-y-5">
                <UiSurface padding="none" class="overflow-hidden">
                    <div class="border-b border-slate-100 bg-slate-50 px-4 py-4">
                        <p class="text-sm font-bold text-slate-900">Informasi Profile</p>
                        <p class="mt-1 text-xs text-slate-500">Nama dan email login admin.</p>
                    </div>

                    <form class="space-y-4 p-4" @submit.prevent="updateProfile">
                        <div class="grid gap-4 md:grid-cols-2">
                            <UiField id="admin_profile_name" label="Nama" required :error="profileForm.errors.name">
                                <InputText
                                    id="admin_profile_name"
                                    v-model="profileForm.name"
                                    class="w-full"
                                    autocomplete="name"
                                    placeholder="Nama lengkap"
                                />
                            </UiField>

                            <UiField id="admin_profile_email" label="Email" required :error="profileForm.errors.email">
                                <InputText
                                    id="admin_profile_email"
                                    v-model="profileForm.email"
                                    class="w-full"
                                    type="email"
                                    autocomplete="email"
                                    placeholder="email@domain.com"
                                />
                            </UiField>
                        </div>

                        <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                            <Button
                                label="Reset"
                                icon="pi pi-refresh"
                                severity="secondary"
                                outlined
                                :disabled="profileForm.processing"
                                @click="resetProfile"
                            />
                            <Button
                                label="Simpan Profile"
                                icon="pi pi-save"
                                type="submit"
                                :loading="profileForm.processing"
                            />
                        </div>
                    </form>
                </UiSurface>

                <UiSurface padding="none" class="overflow-hidden">
                    <div class="border-b border-slate-100 bg-slate-50 px-4 py-4">
                        <p class="text-sm font-bold text-slate-900">Update Password</p>
                        <p class="mt-1 text-xs text-slate-500">Password baru wajib minimal 8 karakter, huruf besar/kecil, angka, dan simbol.</p>
                    </div>

                    <form class="space-y-4 p-4" @submit.prevent="updatePassword">
                        <div class="grid gap-4 md:grid-cols-3">
                            <UiField id="admin_current_password" label="Password saat ini" required :error="passwordForm.errors.current_password">
                                <Password
                                    input-id="admin_current_password"
                                    v-model="passwordForm.current_password"
                                    toggle-mask
                                    :feedback="false"
                                    class="w-full"
                                    input-class="w-full"
                                    autocomplete="current-password"
                                    placeholder="Password saat ini"
                                />
                            </UiField>

                            <UiField id="admin_new_password" label="Password baru" required :error="passwordForm.errors.password">
                                <Password
                                    input-id="admin_new_password"
                                    v-model="passwordForm.password"
                                    toggle-mask
                                    :feedback="false"
                                    class="w-full"
                                    input-class="w-full"
                                    autocomplete="new-password"
                                    placeholder="Password baru"
                                />
                            </UiField>

                            <UiField id="admin_password_confirmation" label="Konfirmasi password" required :error="passwordForm.errors.password_confirmation">
                                <Password
                                    input-id="admin_password_confirmation"
                                    v-model="passwordForm.password_confirmation"
                                    toggle-mask
                                    :feedback="false"
                                    class="w-full"
                                    input-class="w-full"
                                    autocomplete="new-password"
                                    placeholder="Ulangi password baru"
                                />
                            </UiField>
                        </div>

                        <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                            <Button
                                label="Reset"
                                icon="pi pi-refresh"
                                severity="secondary"
                                outlined
                                :disabled="passwordForm.processing"
                                @click="passwordForm.reset(); passwordForm.clearErrors()"
                            />
                            <Button
                                label="Update Password"
                                icon="pi pi-lock"
                                type="submit"
                                :loading="passwordForm.processing"
                            />
                        </div>
                    </form>
                </UiSurface>

                <UiSurface padding="none" class="overflow-hidden">
                    <div class="border-b border-slate-100 bg-slate-50 px-4 py-4">
                        <p class="text-sm font-bold text-slate-900">Activity Log</p>
                        <p class="mt-1 text-xs text-slate-500">Aktivitas terbaru yang dicatat atas akun ini.</p>
                    </div>

                    <div v-if="activities.length" class="divide-y divide-slate-100">
                        <div v-for="activity in activities" :key="activity.id" class="grid gap-3 px-4 py-3 md:grid-cols-[1fr_auto] md:items-center">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <Tag :value="activity.event || 'activity'" severity="secondary" />
                                    <span class="ui-tabular text-xs font-semibold text-slate-500">#{{ activity.id }}</span>
                                </div>
                                <p class="mt-1 truncate text-sm font-semibold text-slate-900">
                                    {{ activity.description || activity.event || "Activity" }}
                                </p>
                                <p class="mt-0.5 truncate text-xs text-slate-500">
                                    {{ activity.subject_type || "Subject" }} <span v-if="activity.subject_id">#{{ activity.subject_id }}</span>
                                </p>
                            </div>
                            <p class="ui-tabular text-xs font-semibold text-slate-500">
                                {{ formatDateTime(activity.created_at) }}
                            </p>
                        </div>
                    </div>

                    <div v-else class="p-4">
                        <UiEmptyState
                            title="Belum ada aktivitas"
                            description="Aktivitas akan muncul setelah ada aksi yang dicatat sistem."
                            icon="pi pi-history"
                        />
                    </div>
                </UiSurface>
            </section>
        </div>
    </AdminLayout>
</template>
