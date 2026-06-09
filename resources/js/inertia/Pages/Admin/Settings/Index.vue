<script setup>
import { Head, useForm, router } from "@inertiajs/vue3";
import AdminLayout from "../../../Layouts/AdminLayout.vue";
import UiSurface from "../../../components/ui/UiSurface.vue";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Dropdown from "primevue/dropdown";
import ColorPicker from "primevue/colorpicker";
import FileUpload from "primevue/fileupload";
import { useToast } from "primevue/usetoast";

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
});

const toast = useToast();

const form = useForm({
    system_mode: props.settings.system_mode || "live",
    app_version: props.settings.app_version || "",
    company_name: props.settings.company_name || "",
    support_email: props.settings.support_email || "",
    primary_color: props.settings.primary_color ? props.settings.primary_color.replace('#', '') : "0f172a",
    app_logo: null, // For file upload
});

const modeOptions = [
    { label: "Live", value: "live" },
    { label: "Maintenance", value: "maintenance" },
    { label: "Off", value: "off" },
];

const submit = () => {
    // Convert color to hex with #
    const data = { ...form };
    if (data.primary_color) {
        data.primary_color = `#${data.primary_color}`;
    }

    form.transform((data) => {
        if (data.primary_color && !data.primary_color.startsWith('#')) {
            data.primary_color = `#${data.primary_color}`;
        }
        return data;
    }).post("/admin/settings", {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: "Berhasil",
                detail: "Pengaturan berhasil diperbarui.",
                life: 3000,
            });
            form.app_logo = null;
        },
    });
};

const handleLogoSelect = (event) => {
    form.app_logo = event.files[0];
};

const clearCache = () => {
    router.post("/admin/settings/clear-cache", {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: "Cache Dibersihkan",
                detail: "Semua cache sistem telah dibersihkan.",
                life: 3000,
            });
        },
    });
};
</script>

<template>
    <AdminLayout title="Pengaturan Sistem - Admin">
        <Head title="Pengaturan Sistem - Admin" />

        <div class="mb-5 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <h1 class="text-balance text-2xl font-black text-slate-900">Pengaturan Sistem</h1>
                <p class="mt-1 text-pretty text-sm text-slate-500">
                    Konfigurasi kontrol performa, tampilan aplikasi, dan informasi umum.
                </p>
            </div>
            <div>
                <Button
                    label="Bersihkan Cache"
                    icon="pi pi-refresh"
                    severity="secondary"
                    outlined
                    @click="clearCache"
                />
            </div>
        </div>

        <form @submit.prevent="submit" class="grid gap-6 xl:grid-cols-[1fr_320px]">
            <div class="space-y-6">
                <!-- System Status -->
                <UiSurface>
                    <div class="mb-4">
                        <h2 class="text-base font-bold text-slate-900">System Status</h2>
                        <p class="mt-1 text-xs text-slate-500">Atur mode ketersediaan aplikasi web ini.</p>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Mode Sistem</label>
                            <Dropdown
                                v-model="form.system_mode"
                                :options="modeOptions"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full"
                            />
                            <p class="mt-1 text-xs text-slate-500">
                                Mode Maintenance/Off akan memblokir pengguna biasa untuk mengakses aplikasi.
                            </p>
                        </div>
                    </div>
                </UiSurface>

                <!-- Appearance -->
                <UiSurface>
                    <div class="mb-4">
                        <h2 class="text-base font-bold text-slate-900">Appearance & Branding</h2>
                        <p class="mt-1 text-xs text-slate-500">Pengaturan visual logo dan tema warna utama.</p>
                    </div>
                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Logo Aplikasi</label>
                            <FileUpload
                                mode="basic"
                                accept="image/*"
                                :maxFileSize="2000000"
                                customUpload
                                @select="handleLogoSelect"
                                chooseLabel="Pilih File Logo"
                                class="mb-2 w-full"
                            />
                            <p v-if="settings.app_logo" class="text-xs text-slate-500">Logo saat ini sudah tersimpan.</p>
                            <p v-else class="text-xs text-slate-500">Belum ada logo khusus (menggunakan bawaan).</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Primary Color</label>
                            <div class="flex items-center gap-3">
                                <ColorPicker v-model="form.primary_color" />
                                <span class="font-mono text-sm text-slate-600 uppercase">#{{ form.primary_color }}</span>
                            </div>
                        </div>
                    </div>
                </UiSurface>

                <!-- General Info -->
                <UiSurface>
                    <div class="mb-4">
                        <h2 class="text-base font-bold text-slate-900">Application Info</h2>
                        <p class="mt-1 text-xs text-slate-500">Data versi, nama perusahaan, dan email bantuan.</p>
                    </div>
                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">App Version</label>
                            <InputText v-model="form.app_version" class="w-full" placeholder="Misal: v1.0.0" />
                        </div>
                        <div class="md:col-span-2 grid gap-6 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-slate-700">Nama Organisasi</label>
                                <InputText v-model="form.company_name" class="w-full" placeholder="Nama perusahaan atau entitas" />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-slate-700">Email Dukungan</label>
                                <InputText v-model="form.support_email" type="email" class="w-full" placeholder="support@domain.com" />
                            </div>
                        </div>
                    </div>
                </UiSurface>
            </div>

            <aside class="space-y-4">
                <UiSurface>
                    <p class="text-sm font-bold text-slate-900">Aksi</p>
                    <p class="mt-1 mb-4 text-xs text-slate-500">Simpan perubahan pengaturan yang telah Anda buat.</p>
                    <Button
                        type="submit"
                        label="Simpan Pengaturan"
                        icon="pi pi-check"
                        class="w-full"
                        :loading="form.processing"
                    />
                </UiSurface>
            </aside>
        </form>
    </AdminLayout>
</template>
