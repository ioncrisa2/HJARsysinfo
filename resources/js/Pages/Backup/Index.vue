<script setup>
import { computed, ref } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";
import UiEmptyState from "../../components/ui/UiEmptyState.vue";
import UiField from "../../components/ui/UiField.vue";
import UiSurface from "../../components/ui/UiSurface.vue";
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import Password from "primevue/password";
import Select from "primevue/select";
import Tag from "primevue/tag";

const props = defineProps({
    artifacts: { type: Array, default: () => [] },
    legacyArtifacts: { type: Array, default: () => [] },
    readiness: { type: Object, default: () => ({}) },
    can: { type: Object, default: () => ({}) },
});

const createDialog = ref(false);
const createType = ref("full");
const createLoading = ref(false);
const importFile = ref(null);
const importLoading = ref(false);
const importProgress = ref(null);
const restoreDialog = ref(false);
const restoreArtifact = ref(null);
const restorePassword = ref("");
const restoreConfirmation = ref("");
const restoreErrors = ref({});
const restoreLoading = ref(false);
const deleteDialog = ref(false);
const deleteArtifact = ref(null);
const actionId = ref(null);

const createOptions = computed(() => [
    ...(props.can.create_database && props.can.create_uploads
        ? [{ label: "Backup lengkap", value: "full", description: "Database dan uploaded files dalam satu restore point." }]
        : []),
    ...(props.can.create_database
        ? [{ label: "Database saja", value: "database", description: "Schema, data, trigger, routine, dan event MySQL." }]
        : []),
    ...(props.can.create_uploads
        ? [{ label: "Uploaded files saja", value: "uploads", description: "Foto pembanding dan aset pengaturan." }]
        : []),
]);

const summary = computed(() => ({
    total: props.artifacts.length,
    verified: props.artifacts.filter((item) => item.verified).length,
    size: props.artifacts.reduce((total, item) => total + Number(item.size || 0), 0),
}));

const formatBytes = (bytes) => {
    if (! bytes) return "0 B";
    const units = ["B", "KB", "MB", "GB", "TB"];
    const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
    return `${(bytes / (1024 ** index)).toFixed(index === 0 ? 0 : 1)} ${units[index]}`;
};

const formatDate = (value) => {
    if (! value) return "-";
    return new Intl.DateTimeFormat("id-ID", {
        dateStyle: "medium",
        timeStyle: "short",
    }).format(new Date(value));
};

const typeSeverity = (type) => type === "full" ? "success" : type === "database" ? "info" : "secondary";
const canRestoreUploads = (artifact) => {
    return ["full", "uploads"].includes(artifact.type)
        && props.can.restore_uploads
        && props.can.restore_operator;
};

const runCreate = () => {
    createLoading.value = true;
    router.post("/app/backup/artifacts", { type: createType.value }, {
        preserveScroll: true,
        onSuccess: () => { createDialog.value = false; },
        onFinish: () => { createLoading.value = false; },
    });
};

const openCreate = () => {
    createType.value = createOptions.value[0]?.value ?? "uploads";
    createDialog.value = true;
};

const runImport = () => {
    if (! importFile.value) return;
    const body = new FormData();
    body.append("package", importFile.value);
    importLoading.value = true;
    importProgress.value = 0;
    router.post("/app/backup/import", body, {
        forceFormData: true,
        preserveScroll: true,
        onProgress: (progress) => { importProgress.value = progress?.percentage ?? null; },
        onSuccess: () => {
            importFile.value = null;
            const input = document.getElementById("backup_package");
            if (input) input.value = "";
        },
        onFinish: () => {
            importLoading.value = false;
            importProgress.value = null;
        },
    });
};

const verify = (artifact) => {
    actionId.value = artifact.id;
    router.post(`/app/backup/artifacts/${artifact.id}/verify`, {}, {
        preserveScroll: true,
        onFinish: () => { actionId.value = null; },
    });
};

const openRestore = (artifact) => {
    restoreArtifact.value = artifact;
    restorePassword.value = "";
    restoreConfirmation.value = "";
    restoreErrors.value = {};
    restoreDialog.value = true;
};

const runRestore = () => {
    restoreLoading.value = true;
    restoreErrors.value = {};
    router.post(`/app/backup/artifacts/${restoreArtifact.value.id}/restore-uploads`, {
        current_password: restorePassword.value,
        confirmation: restoreConfirmation.value,
    }, {
        preserveScroll: true,
        onError: (errors) => { restoreErrors.value = errors; },
        onSuccess: () => { restoreDialog.value = false; },
        onFinish: () => { restoreLoading.value = false; },
    });
};

const openDelete = (artifact) => {
    deleteArtifact.value = artifact;
    deleteDialog.value = true;
};

const runDelete = () => {
    actionId.value = deleteArtifact.value.id;
    router.delete(`/app/backup/artifacts/${deleteArtifact.value.id}`, {
        preserveScroll: true,
        onSuccess: () => { deleteDialog.value = false; },
        onFinish: () => { actionId.value = null; },
    });
};
</script>

<template>
    <AppLayout title="Backup Sistem">
        <Head title="Backup Sistem" />

        <div class="space-y-5">
            <header class="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.14em] text-slate-500">
                        <i class="pi pi-shield" aria-hidden="true" />
                        Recovery center
                    </div>
                    <h1 class="mt-2 text-2xl font-black text-slate-950">Backup Sistem</h1>
                    <p class="mt-1 max-w-2xl text-sm text-slate-600">
                        Kelola restore point terverifikasi untuk database dan file operasional.
                    </p>
                </div>

                <Button
                    v-if="createOptions.length"
                    label="Buat backup"
                    icon="pi pi-plus"
                    @click="openCreate"
                />
            </header>

            <div
                v-if="!readiness.restore_enabled"
                class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-950"
                role="status"
            >
                <i class="pi pi-lock mt-0.5" aria-hidden="true" />
                <div>
                    <p class="text-sm font-bold">Restore dikunci oleh konfigurasi server</p>
                    <p class="mt-0.5 text-xs leading-5 text-amber-800">
                        Backup, import, verify, dan download tetap tersedia. Operator harus mengaktifkan restore setelah readiness production dipenuhi.
                    </p>
                </div>
            </div>

            <section class="grid grid-cols-3 divide-x divide-slate-200 rounded-lg border border-slate-200 bg-white">
                <div class="px-4 py-3">
                    <p class="text-xs font-semibold text-slate-500">Restore point</p>
                    <p class="ui-tabular mt-1 text-xl font-black text-slate-950">{{ summary.total }}</p>
                </div>
                <div class="px-4 py-3">
                    <p class="text-xs font-semibold text-slate-500">Terverifikasi</p>
                    <p class="ui-tabular mt-1 text-xl font-black text-slate-950">{{ summary.verified }}</p>
                </div>
                <div class="px-4 py-3">
                    <p class="text-xs font-semibold text-slate-500">Penyimpanan</p>
                    <p class="ui-tabular mt-1 text-xl font-black text-slate-950">{{ formatBytes(summary.size) }}</p>
                </div>
            </section>

            <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_300px]">
                <UiSurface padding="none" class="overflow-hidden">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
                        <div>
                            <h2 class="text-sm font-bold text-slate-950">Restore point</h2>
                            <p class="mt-0.5 text-xs text-slate-500">Paket `.sbackup` private dengan signature dan checksum.</p>
                        </div>
                        <Button icon="pi pi-refresh" text rounded aria-label="Muat ulang" @click="router.reload({ preserveScroll: true })" />
                    </div>

                    <div v-if="artifacts.length" class="overflow-x-auto">
                        <table class="w-full min-w-[780px] text-left">
                            <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3 font-bold">Backup</th>
                                    <th class="px-3 py-3 font-bold">Status</th>
                                    <th class="px-3 py-3 font-bold">Ukuran</th>
                                    <th class="px-3 py-3 font-bold">Dibuat</th>
                                    <th class="px-4 py-3 text-right font-bold">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="artifact in artifacts" :key="artifact.id" class="hover:bg-slate-50/70">
                                    <td class="px-4 py-3">
                                        <div class="flex items-start gap-3">
                                            <div class="flex size-9 shrink-0 items-center justify-center rounded-md bg-slate-100 text-slate-600">
                                                <i :class="artifact.type === 'database' ? 'pi pi-database' : 'pi pi-box'" aria-hidden="true" />
                                            </div>
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <p class="max-w-[340px] truncate text-sm font-bold text-slate-900" :title="artifact.filename">{{ artifact.filename }}</p>
                                                    <Tag :value="artifact.type_label" :severity="typeSeverity(artifact.type)" />
                                                </div>
                                                <p class="ui-tabular mt-1 text-[11px] text-slate-500">SHA-256 {{ artifact.checksum_short }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3">
                                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold" :class="artifact.verified ? 'text-emerald-700' : 'text-amber-700'">
                                            <i :class="artifact.verified ? 'pi pi-verified' : 'pi pi-exclamation-circle'" aria-hidden="true" />
                                            {{ artifact.verified ? "Verified" : "Perlu verifikasi" }}
                                        </span>
                                    </td>
                                    <td class="ui-tabular whitespace-nowrap px-3 py-3 text-xs font-semibold text-slate-700">{{ artifact.size_label }}</td>
                                    <td class="px-3 py-3">
                                        <p class="whitespace-nowrap text-xs font-semibold text-slate-700">{{ formatDate(artifact.created_at) }}</p>
                                        <p class="mt-0.5 text-[11px] text-slate-500">{{ artifact.created_by?.name || "System" }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-1">
                                            <Button
                                                v-if="can.download"
                                                as="a"
                                                :href="`/app/backup/artifacts/${artifact.id}/download`"
                                                icon="pi pi-download"
                                                text
                                                rounded
                                                aria-label="Download backup"
                                                title="Download"
                                            />
                                            <Button
                                                v-if="can.verify"
                                                icon="pi pi-check-circle"
                                                text
                                                rounded
                                                aria-label="Verifikasi backup"
                                                title="Verifikasi"
                                                :loading="actionId === artifact.id"
                                                @click="verify(artifact)"
                                            />
                                            <Button
                                                v-if="canRestoreUploads(artifact)"
                                                icon="pi pi-history"
                                                text
                                                rounded
                                                severity="warn"
                                                aria-label="Restore uploaded files"
                                                title="Restore uploaded files"
                                                :disabled="!readiness.uploads_restore_ready"
                                                @click="openRestore(artifact)"
                                            />
                                            <Button
                                                v-if="can.delete"
                                                icon="pi pi-trash"
                                                text
                                                rounded
                                                severity="danger"
                                                aria-label="Hapus backup"
                                                title="Hapus"
                                                @click="openDelete(artifact)"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="p-5">
                        <UiEmptyState
                            title="Belum ada restore point"
                            description="Buat backup baru atau import paket `.sbackup` yang pernah diunduh."
                            icon="pi pi-box"
                        />
                    </div>
                </UiSurface>

                <aside class="space-y-4">
                    <UiSurface v-if="can.import">
                        <h2 class="text-sm font-bold text-slate-950">Import paket</h2>
                        <p class="mt-1 text-xs leading-5 text-slate-500">
                            Hanya paket `.sbackup` dengan signature sistem yang diterima.
                        </p>
                        <UiField id="backup_package" label="Pilih file" class="mt-4">
                            <input
                                id="backup_package"
                                type="file"
                                accept=".sbackup"
                                class="block w-full text-xs text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-bold file:text-slate-700 hover:file:bg-slate-200"
                                @change="importFile = $event.target.files?.[0] ?? null"
                            />
                        </UiField>
                        <Button
                            label="Import & verifikasi"
                            icon="pi pi-upload"
                            severity="secondary"
                            outlined
                            class="mt-3 w-full"
                            :disabled="!importFile"
                            :loading="importLoading"
                            @click="runImport"
                        />
                        <p v-if="importProgress !== null" class="ui-tabular mt-2 text-center text-xs font-semibold text-slate-500">
                            Upload {{ importProgress }}%
                        </p>
                    </UiSurface>

                    <UiSurface>
                        <h2 class="text-sm font-bold text-slate-950">Readiness</h2>
                        <dl class="mt-3 space-y-2.5">
                            <div v-for="item in [
                                ['Storage private', readiness.storage_writable],
                                ['Signature key', readiness.signing_key],
                                ['ZipArchive', readiness.zip],
                                ['Restore uploads', readiness.uploads_restore_ready],
                            ]" :key="item[0]" class="flex items-center justify-between gap-3">
                                <dt class="text-xs font-semibold text-slate-600">{{ item[0] }}</dt>
                                <dd class="flex items-center gap-1.5 text-xs font-bold" :class="item[1] ? 'text-emerald-700' : 'text-slate-500'">
                                    <i :class="item[1] ? 'pi pi-check-circle' : 'pi pi-minus-circle'" aria-hidden="true" />
                                    {{ item[1] ? "Ready" : "Locked" }}
                                </dd>
                            </div>
                        </dl>
                        <p class="mt-4 border-t border-slate-100 pt-3 text-xs leading-5 text-slate-500">
                            {{ readiness.database_restore_note }}
                        </p>
                    </UiSurface>

                    <details v-if="legacyArtifacts.length" class="rounded-lg border border-slate-200 bg-white">
                        <summary class="cursor-pointer px-4 py-3 text-xs font-bold text-slate-700">
                            {{ legacyArtifacts.length }} backup lama
                        </summary>
                        <div class="space-y-2 border-t border-slate-100 p-3">
                            <div v-for="legacy in legacyArtifacts" :key="legacy.id" class="flex items-center justify-between gap-2 rounded-md bg-slate-50 p-2">
                                <div class="min-w-0">
                                    <p class="truncate text-xs font-semibold text-slate-700" :title="legacy.filename">{{ legacy.filename }}</p>
                                    <p class="mt-0.5 text-[11px] text-slate-500">{{ legacy.size_label }} · download only</p>
                                </div>
                                <Button
                                    v-if="can.download"
                                    as="a"
                                    :href="`/app/backup/artifacts/${legacy.id}/download`"
                                    icon="pi pi-download"
                                    text
                                    rounded
                                    aria-label="Download backup lama"
                                />
                            </div>
                        </div>
                    </details>
                </aside>
            </div>
        </div>

        <Dialog v-model:visible="createDialog" modal :draggable="false" header="Buat restore point" style="width: min(520px, 100%)">
            <div class="space-y-4">
                <p class="text-sm leading-6 text-slate-600">Pilih cakupan backup. Paket disimpan secara private dan dapat diunduh setelah proses selesai.</p>
                <UiField id="backup_type" label="Cakupan backup" required>
                    <Select
                        id="backup_type"
                        v-model="createType"
                        :options="createOptions"
                        option-label="label"
                        option-value="value"
                        class="w-full"
                    />
                </UiField>
                <div v-if="createOptions.find((item) => item.value === createType)" class="rounded-md bg-slate-50 p-3 text-xs leading-5 text-slate-600">
                    {{ createOptions.find((item) => item.value === createType).description }}
                </div>
            </div>
            <template #footer>
                <Button label="Batal" severity="secondary" text :disabled="createLoading" @click="createDialog = false" />
                <Button label="Buat backup" icon="pi pi-shield" :loading="createLoading" @click="runCreate" />
            </template>
        </Dialog>

        <Dialog v-model:visible="restoreDialog" modal :draggable="false" header="Restore uploaded files" style="width: min(560px, 100%)">
            <div class="space-y-4">
                <div class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-900">
                    Isi folder foto dan aset pengaturan saat ini akan diganti. Sistem membuat backup keselamatan terlebih dahulu dan melakukan rollback jika swap gagal.
                </div>
                <UiField id="restore_password" label="Password saat ini" required :error="restoreErrors.current_password">
                    <Password
                        input-id="restore_password"
                        v-model="restorePassword"
                        :feedback="false"
                        toggle-mask
                        fluid
                        autocomplete="current-password"
                    />
                </UiField>
                <UiField
                    id="restore_confirmation"
                    :label="`Ketik RESTORE ${restoreArtifact?.id}`"
                    required
                    :error="restoreErrors.confirmation"
                >
                    <InputText id="restore_confirmation" v-model="restoreConfirmation" class="w-full ui-tabular" autocomplete="off" />
                </UiField>
            </div>
            <template #footer>
                <Button label="Batal" severity="secondary" text :disabled="restoreLoading" @click="restoreDialog = false" />
                <Button
                    label="Restore sekarang"
                    icon="pi pi-history"
                    severity="danger"
                    :loading="restoreLoading"
                    :disabled="restoreConfirmation !== `RESTORE ${restoreArtifact?.id}` || !restorePassword"
                    @click="runRestore"
                />
            </template>
        </Dialog>

        <Dialog v-model:visible="deleteDialog" modal :draggable="false" header="Hapus backup" style="width: min(460px, 100%)">
            <p class="text-sm leading-6 text-slate-700">
                File <strong>{{ deleteArtifact?.filename }}</strong> akan dihapus permanen dari server.
            </p>
            <template #footer>
                <Button label="Batal" severity="secondary" text @click="deleteDialog = false" />
                <Button label="Hapus backup" icon="pi pi-trash" severity="danger" :loading="actionId === deleteArtifact?.id" @click="runDelete" />
            </template>
        </Dialog>
    </AppLayout>
</template>
