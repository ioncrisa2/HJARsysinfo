<script setup>
import { Head, useForm, usePage } from "@inertiajs/vue3";
import InputText from "primevue/inputtext";
import Password from "primevue/password";
import Button from "primevue/button";
import TopNavLayout from "../../Layouts/TopNavLayout.vue";
import UiSurface from "../../components/ui/UiSurface.vue";
import UiSectionHeader from "../../components/ui/UiSectionHeader.vue";
import UiField from "../../components/ui/UiField.vue";

defineOptions({ layout: TopNavLayout });

const page = usePage();

const props = defineProps({
    user: { type: Object, default: () => ({}) },
});

const initials = (name) =>
    (name ?? "U")
        .split(" ")
        .slice(0, 2)
        .map((w) => w[0])
        .join("")
        .toUpperCase();

const form = useForm({
    name: props.user?.name ?? "",
    email: props.user?.email ?? "",
    password: "",
    password_confirmation: "",
});

const submit = () => {
    form.put("/profile", {
        onSuccess: () => form.reset("password", "password_confirmation"),
    });
};
</script>

<template>
    <Head title="Profil" />

    <div class="space-y-4 py-3 sm:py-5">
        <div>
            <p class="text-xs text-slate-500">Akun</p>
            <h1 class="text-balance text-xl font-semibold text-slate-900">Profil Pengguna</h1>
            <p class="mt-1 text-pretty text-sm text-slate-500">
                Perbarui informasi akun dan password.
            </p>
        </div>

        <div
            v-if="page.props.flash?.success"
            class="rounded-[var(--radius-md)] border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700"
            role="status"
        >
            <span class="inline-flex items-center gap-2">
                <i class="pi pi-check-circle text-[14px] text-amber-600" aria-hidden="true" />
                {{ page.props.flash.success }}
            </span>
        </div>

        <div class="grid gap-4 lg:grid-cols-[320px_1fr]">
            <div class="space-y-4">
                <UiSurface padding="none" class="overflow-hidden">
                    <div class="border-b border-slate-100 bg-slate-50/70 px-4 py-3">
                        <UiSectionHeader title="Ringkasan" subtitle="Identitas akun dan peran aktif." icon="pi pi-user" />
                    </div>

                    <div class="p-4">
                        <div class="flex items-start gap-3">
                            <div
                                class="flex size-12 items-center justify-center rounded-[var(--radius-md)] border border-slate-200 bg-white text-base font-semibold text-slate-800"
                                aria-hidden="true"
                            >
                                {{ initials(user?.name) }}
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">
                                    {{ user?.name ?? "Pengguna" }}
                                </p>
                                <p class="truncate text-sm text-slate-500">
                                    {{ user?.email ?? "" }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 border-t border-slate-100 pt-4">
                            <p class="text-xs font-semibold text-slate-700">Peran</p>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <span
                                    v-for="role in user?.roles ?? []"
                                    :key="role"
                                    class="rounded-full border border-slate-200 bg-white px-2.5 py-0.5 text-xs font-semibold text-slate-700"
                                >
                                    {{ role }}
                                </span>
                                <span v-if="(user?.roles ?? []).length === 0" class="text-xs text-slate-500">
                                    Tidak ada peran.
                                </span>
                            </div>
                        </div>
                    </div>
                </UiSurface>

                <UiSurface v-if="(user?.permissions ?? []).length > 0" padding="none" class="overflow-hidden">
                    <div class="border-b border-slate-100 bg-slate-50/70 px-4 py-3">
                        <UiSectionHeader title="Hak Akses" subtitle="Permission yang dimiliki user." icon="pi pi-key" />
                    </div>
                    <div class="p-4">
                        <div class="flex flex-wrap gap-1.5">
                            <span
                                v-for="perm in user?.permissions ?? []"
                                :key="perm"
                                class="rounded-full border border-slate-200 bg-white px-2.5 py-0.5 text-xs font-semibold text-slate-700"
                            >
                                {{ perm }}
                            </span>
                        </div>
                    </div>
                </UiSurface>
            </div>

            <div class="space-y-4">
                <UiSurface padding="none" class="overflow-hidden">
                    <div class="border-b border-slate-100 bg-slate-50/70 px-4 py-3">
                        <UiSectionHeader title="Informasi Akun" subtitle="Nama dan email yang digunakan untuk login." icon="pi pi-id-card" />
                    </div>

                    <div class="grid gap-4 p-4 sm:grid-cols-2">
                        <UiField id="profile_name" label="Nama lengkap" :required="true" :error="form.errors.name">
                            <InputText
                                v-model="form.name"
                                id="profile_name"
                                class="w-full filter-light"
                                placeholder="Nama lengkap"
                                autocomplete="name"
                            />
                        </UiField>

                        <UiField id="profile_email" label="Alamat email" :required="true" :error="form.errors.email">
                            <InputText
                                v-model="form.email"
                                id="profile_email"
                                type="email"
                                class="w-full filter-light"
                                placeholder="email@contoh.com"
                                autocomplete="email"
                            />
                        </UiField>
                    </div>
                </UiSurface>

                <UiSurface padding="none" class="overflow-hidden">
                    <div class="border-b border-slate-100 bg-slate-50/70 px-4 py-3">
                        <UiSectionHeader title="Password" subtitle="Kosongkan jika tidak ingin mengganti." icon="pi pi-lock" />
                    </div>

                    <div class="grid gap-4 p-4 sm:grid-cols-2">
                        <UiField id="profile_password" label="Password baru" :error="form.errors.password">
                            <Password
                                v-model="form.password"
                                inputId="profile_password"
                                toggle-mask
                                :feedback="false"
                                class="w-full filter-light"
                                input-class="w-full filter-light"
                                placeholder="********"
                                autocomplete="new-password"
                            />
                        </UiField>

                        <UiField id="profile_password_confirmation" label="Konfirmasi password">
                            <Password
                                v-model="form.password_confirmation"
                                inputId="profile_password_confirmation"
                                toggle-mask
                                :feedback="false"
                                class="w-full filter-light"
                                input-class="w-full filter-light"
                                placeholder="********"
                                autocomplete="new-password"
                            />
                        </UiField>
                    </div>
                </UiSurface>

                <div class="flex items-center justify-end gap-2">
                    <Button
                        label="Reset"
                        icon="pi pi-refresh"
                        severity="secondary"
                        outlined
                        :disabled="form.processing"
                        @click="form.reset()"
                    />
                    <Button
                        label="Simpan"
                        icon="pi pi-save"
                        :loading="form.processing"
                        @click="submit"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

