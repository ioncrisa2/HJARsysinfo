<script setup>
import { computed, ref } from "vue";
import { Head, router } from "@inertiajs/vue3";
import { useToast } from "primevue/usetoast";
import AdminLayout from "../../../Layouts/AdminLayout.vue";
import UiEmptyState from "../../../components/ui/UiEmptyState.vue";
import UiSurface from "../../../components/ui/UiSurface.vue";
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import Tag from "primevue/tag";
import { appendCsrfHeader } from "../../../utils/csrf";

const props = defineProps({
    meta: { type: Object, default: () => ({}) },
    history: { type: Object, default: () => ({ database: [], uploads: [] }) },
    can: { type: Object, default: () => ({}) },
});

const toast = useToast();

const confirmDialog = ref(false);
const pendingType = ref("database");
const loadingType = ref(null);

const actionMeta = computed(() => {
    if (pendingType.value === "uploads") {
        return {
            title: "Backup Uploaded Files",
            endpoint: "/admin/backup/uploads",
            icon: "pi pi-folder",
            description: "Sistem akan membuat file ZIP dari storage/app/public.",
            success: "Backup uploaded files berhasil dibuat.",
            button: "Backup Uploaded Files",
        };
    }

    return {
        title: "Backup Database",
        endpoint: "/admin/backup/database",
        icon: "pi pi-database",
        description: "Sistem akan membuat file SQL dari database aktif.",
        success: "Backup database berhasil dibuat.",
        button: "Backup Database",
    };
});

const isLoading = computed(() => loadingType.value !== null);

const openConfirm = (type) => {
    if ((type === "database" && !props.can.database) || (type === "uploads" && !props.can.uploads)) return;

    pendingType.value = type;
    confirmDialog.value = true;
};

const contentDispositionFilename = (header) => {
    if (!header) return null;

    const utf8Match = header.match(/filename\*=UTF-8''([^;]+)/i);
    if (utf8Match?.[1]) return decodeURIComponent(utf8Match[1]);

    const match = header.match(/filename="?([^"]+)"?/i);
    return match?.[1] ?? null;
};

const downloadBlob = async (response) => {
    const blob = await response.blob();
    const filename = contentDispositionFilename(response.headers.get("Content-Disposition")) || "backup-file";
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");

    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
};

const errorMessage = async (response) => {
    const contentType = response.headers.get("Content-Type") ?? "";

    if (contentType.includes("application/json")) {
        const payload = await response.json().catch(() => null);
        return payload?.message || "Backup gagal dibuat.";
    }

    return `Backup gagal dibuat (HTTP ${response.status}).`;
};

const runBackup = async () => {
    if ((pendingType.value === "database" && !props.can.database) || (pendingType.value === "uploads" && !props.can.uploads)) return;

    const meta = actionMeta.value;
    loadingType.value = pendingType.value;
    confirmDialog.value = false;

    try {
        const headers = appendCsrfHeader(new Headers({
            Accept: "application/octet-stream, application/json",
            "X-Requested-With": "XMLHttpRequest",
        }));

        const response = await fetch(meta.endpoint, {
            method: "POST",
            credentials: "same-origin",
            headers,
        });

        if (!response.ok) {
            throw new Error(await errorMessage(response));
        }

        await downloadBlob(response);
        toast.add({ severity: "success", summary: "Backup berhasil", detail: meta.success, life: 4000 });
        router.reload({ only: ["history"], preserveScroll: true });
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Backup gagal",
            detail: error?.message || "Terjadi kesalahan saat membuat backup.",
            life: 6000,
        });
    } finally {
        loadingType.value = null;
    }
};

const formatDate = (value) => {
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
    <AdminLayout title="Backup Sistem - Admin">
        <Head title="Backup Sistem - Admin" />

        <div class="mb-5 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <h1 class="text-balance text-2xl font-black text-slate-900">Backup Sistem</h1>
                <p class="mt-1 text-pretty text-sm text-slate-500">
                    Buat backup database dan uploaded files untuk arsip operasional.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <Button
                    v-if="props.can.database"
                    label="Backup Database"
                    icon="pi pi-database"
                    :loading="loadingType === 'database'"
                    :disabled="isLoading"
                    @click="openConfirm('database')"
                />
                <Button
                    v-if="props.can.uploads"
                    label="Backup Uploaded Files"
                    icon="pi pi-folder"
                    severity="secondary"
                    outlined
                    :loading="loadingType === 'uploads'"
                    :disabled="isLoading"
                    @click="openConfirm('uploads')"
                />
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1fr_320px]">
            <div class="space-y-5">
                <div class="grid gap-4 md:grid-cols-2">
                    <UiSurface>
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-bold text-slate-900">Database Backup</p>
                                <p class="mt-1 text-pretty text-xs text-slate-500">
                                    Export database aktif sebagai file SQL.
                                </p>
                            </div>
                            <div class="flex size-11 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                                <i class="pi pi-database" />
                            </div>
                        </div>

                        <dl class="mt-5 space-y-3">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-xs font-semibold text-slate-500">Connection</dt>
                                <dd class="ui-tabular truncate text-sm font-bold text-slate-900">{{ meta.database?.connection }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-xs font-semibold text-slate-500">Driver</dt>
                                <dd class="ui-tabular truncate text-sm font-bold text-slate-900">{{ meta.database?.driver }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-xs font-semibold text-slate-500">Database</dt>
                                <dd class="ui-tabular max-w-[180px] truncate text-sm font-bold text-slate-900">{{ meta.database?.database }}</dd>
                            </div>
                        </dl>

                        <Button
                            v-if="props.can.database"
                            label="Backup Database"
                            icon="pi pi-download"
                            class="mt-5 w-full"
                            :loading="loadingType === 'database'"
                            :disabled="isLoading"
                            @click="openConfirm('database')"
                        />
                    </UiSurface>

                    <UiSurface>
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-bold text-slate-900">Uploaded Files Backup</p>
                                <p class="mt-1 text-pretty text-xs text-slate-500">
                                    Compress storage/app/public sebagai file ZIP.
                                </p>
                            </div>
                            <div class="flex size-11 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                                <i class="pi pi-folder" />
                            </div>
                        </div>

                        <dl class="mt-5 space-y-3">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-xs font-semibold text-slate-500">ZipArchive</dt>
                                <dd>
                                    <Tag :value="meta.requirements?.zip ? 'Aktif' : 'Tidak aktif'" :severity="meta.requirements?.zip ? 'success' : 'danger'" />
                                </dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-xs font-semibold text-slate-500">Source</dt>
                                <dd class="max-w-[180px] truncate text-xs font-semibold text-slate-700" :title="meta.paths?.source_uploads">
                                    {{ meta.paths?.source_uploads }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-xs font-semibold text-slate-500">Target</dt>
                                <dd class="max-w-[180px] truncate text-xs font-semibold text-slate-700" :title="meta.paths?.uploads">
                                    {{ meta.paths?.uploads }}
                                </dd>
                            </div>
                        </dl>

                        <Button
                            v-if="props.can.uploads"
                            label="Backup Uploaded Files"
                            icon="pi pi-download"
                            severity="secondary"
                            outlined
                            class="mt-5 w-full"
                            :loading="loadingType === 'uploads'"
                            :disabled="isLoading || !meta.requirements?.zip"
                            @click="openConfirm('uploads')"
                        />
                    </UiSurface>
                </div>

                <UiSurface padding="none" class="overflow-hidden">
                    <div class="border-b border-slate-100 bg-slate-50 px-4 py-4">
                        <p class="text-sm font-bold text-slate-900">Backup Database Terbaru</p>
                        <p class="mt-1 text-xs text-slate-500">File disimpan di storage/app/backups/database.</p>
                    </div>

                    <div v-if="history.database?.length" class="divide-y divide-slate-100">
                        <div v-for="file in history.database" :key="file.name" class="flex items-center justify-between gap-4 px-4 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ file.name }}</p>
                                <p class="ui-tabular mt-0.5 text-xs text-slate-500">{{ formatDate(file.created_at) }}</p>
                            </div>
                            <span class="ui-tabular shrink-0 text-xs font-bold text-slate-600">{{ file.size_label }}</span>
                        </div>
                    </div>

                    <div v-else class="p-4">
                        <UiEmptyState
                            title="Belum ada backup database"
                            description="Jalankan backup database untuk membuat file pertama."
                            icon="pi pi-database"
                        />
                    </div>
                </UiSurface>

                <UiSurface padding="none" class="overflow-hidden">
                    <div class="border-b border-slate-100 bg-slate-50 px-4 py-4">
                        <p class="text-sm font-bold text-slate-900">Backup Uploaded Files Terbaru</p>
                        <p class="mt-1 text-xs text-slate-500">File disimpan di storage/app/backups/uploads.</p>
                    </div>

                    <div v-if="history.uploads?.length" class="divide-y divide-slate-100">
                        <div v-for="file in history.uploads" :key="file.name" class="flex items-center justify-between gap-4 px-4 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ file.name }}</p>
                                <p class="ui-tabular mt-0.5 text-xs text-slate-500">{{ formatDate(file.created_at) }}</p>
                            </div>
                            <span class="ui-tabular shrink-0 text-xs font-bold text-slate-600">{{ file.size_label }}</span>
                        </div>
                    </div>

                    <div v-else class="p-4">
                        <UiEmptyState
                            title="Belum ada backup upload"
                            description="Jalankan backup uploaded files untuk membuat file pertama."
                            icon="pi pi-folder"
                        />
                    </div>
                </UiSurface>
            </div>

            <aside class="space-y-4">
                <UiSurface>
                    <p class="text-sm font-bold text-slate-900">Requirement</p>
                    <div class="mt-4 space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs font-semibold text-slate-500">mysqldump</span>
                            <span class="ui-tabular max-w-[160px] truncate text-xs font-bold text-slate-800" :title="meta.requirements?.mysqldump">
                                {{ meta.requirements?.mysqldump }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs font-semibold text-slate-500">DB fallback</span>
                            <Tag :value="meta.requirements?.database_fallback || 'PHP PDO'" severity="info" />
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs font-semibold text-slate-500">ZipArchive</span>
                            <Tag :value="meta.requirements?.zip ? 'Ready' : 'Missing'" :severity="meta.requirements?.zip ? 'success' : 'danger'" />
                        </div>
                    </div>
                </UiSurface>

                <UiSurface>
                    <p class="text-sm font-bold text-slate-900">Catatan Operasional</p>
                    <ul class="mt-3 space-y-2 text-pretty text-xs font-medium text-slate-600">
                        <li>Backup database mencoba mysqldump jika tersedia, lalu fallback ke PHP PDO.</li>
                        <li>Untuk database besar, set MYSQLDUMP_BINARY agar proses backup lebih cepat dan stabil.</li>
                        <li>Backup upload hanya mengambil isi storage/app/public.</li>
                        <li>File backup tersimpan di storage/app/backups dan langsung diunduh setelah selesai.</li>
                    </ul>
                </UiSurface>
            </aside>
        </div>

        <Dialog
            v-model:visible="confirmDialog"
            modal
            :draggable="false"
            :header="actionMeta.title"
            style="width: min(520px, 100%)"
        >
            <div class="space-y-3">
                <div class="flex size-12 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                    <i :class="actionMeta.icon" />
                </div>
                <p class="text-pretty text-sm text-slate-700">{{ actionMeta.description }}</p>
                <p class="text-pretty text-xs font-medium text-slate-500">
                    Proses dapat memakan waktu untuk database atau folder upload yang besar.
                </p>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button label="Batal" severity="secondary" outlined :disabled="isLoading" @click="confirmDialog = false" />
                    <Button :label="actionMeta.button" icon="pi pi-download" :loading="isLoading" @click="runBackup" />
                </div>
            </template>
        </Dialog>
    </AdminLayout>
</template>
