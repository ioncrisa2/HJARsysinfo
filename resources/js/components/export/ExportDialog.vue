<script setup>
import { computed, watch } from "vue";
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import MultiSelect from "primevue/multiselect";
import Select from "primevue/select";

const props = defineProps({
    visible: { type: Boolean, required: true },
    format: { type: String, default: "excel" },
    mode: { type: String, default: "summary" },
    profile: { type: String, default: "lengkap" },
    columns: { type: Array, default: () => [] },
    dataset: { type: String, default: "all" },
    scope: { type: String, default: "selected" },
    exportCount: { type: Number, default: 0 },
    summary: { type: Object, required: true },
    configuration: { type: Object, required: true },
    previewLoading: { type: Boolean, default: false },
    previewError: { type: String, default: null },
    previewWithoutCoordinates: { type: Number, default: 0 },
});

const emit = defineEmits([
    "update:visible", "update:format", "update:mode", "update:profile", "update:columns", "update:dataset", "confirmExport",
]);
const bind = (name) => computed({ get: () => props[name], set: (value) => emit(`update:${name}`, value) });
const dialogVisible = bind("visible");
const selectedFormat = bind("format");
const selectedMode = bind("mode");
const selectedProfile = bind("profile");
const selectedColumns = bind("columns");
const selectedDataset = bind("dataset");

const formatOptions = [
    { label: "Excel (.xlsx)", value: "excel" },
    { label: "PDF", value: "pdf" },
    { label: "CSV UTF-8", value: "csv" },
    { label: "GeoJSON", value: "geojson" },
    { label: "KML", value: "kml" },
];
const modeOptions = [
    { label: "Ringkasan", value: "summary" },
    { label: "Detail (satu data per halaman)", value: "detail" },
];
const datasetOptions = [
    { label: "Semua data", value: "all" },
    { label: "Hanya data lengkap", value: "complete" },
    { label: "Laporan data bermasalah", value: "issues" },
];
const limitKey = computed(() => selectedFormat.value === "pdf" ? `pdf_${selectedMode.value}` : selectedFormat.value);
const syncLimit = computed(() => Number(props.summary?.limits?.[limitKey.value] ?? 5000));
const queued = computed(() => props.exportCount > syncLimit.value);
const formatNumber = (value) => new Intl.NumberFormat("id-ID").format(Number(value ?? 0));
const columnLabel = (column) => props.configuration.columns.find((item) => item.value === column)?.label ?? column;
const moveColumn = (index, direction) => {
    const target = index + direction;
    if (target < 0 || target >= selectedColumns.value.length) return;
    const columns = [...selectedColumns.value];
    [columns[index], columns[target]] = [columns[target], columns[index]];
    selectedColumns.value = columns;
};

watch(selectedFormat, (format) => {
    const profile = format === "pdf" ? "ringkas" : ["geojson", "kml"].includes(format) ? "geospasial" : null;
    if (!profile) return;
    selectedProfile.value = profile;
    selectedColumns.value = props.configuration.profiles.find((item) => item.value === profile)?.columns ?? [];
});

watch(selectedDataset, (dataset) => {
    if (dataset === "issues" && !selectedColumns.value.includes("quality_issues")) {
        selectedColumns.value = [...selectedColumns.value, "quality_issues"];
    }
});
</script>

<template>
    <Dialog v-model:visible="dialogVisible" modal :draggable="false" header="Siapkan Export" style="width: min(640px, 100%)">
        <div class="space-y-5">
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="space-y-1.5 text-sm font-semibold text-slate-700">
                    <span>Format file</span>
                    <Select v-model="selectedFormat" :options="formatOptions" option-label="label" option-value="value" class="w-full" />
                </label>
                <label v-if="selectedFormat === 'pdf'" class="space-y-1.5 text-sm font-semibold text-slate-700">
                    <span>Mode PDF</span>
                    <Select v-model="selectedMode" :options="modeOptions" option-label="label" option-value="value" class="w-full" />
                </label>
                <label class="space-y-1.5 text-sm font-semibold text-slate-700" :class="selectedFormat !== 'pdf' ? 'sm:col-start-2' : ''">
                    <span>Profil kolom</span>
                    <Select v-model="selectedProfile" :options="configuration.profiles" option-label="label" option-value="value" class="w-full" />
                </label>
            </div>

            <label class="block space-y-1.5 text-sm font-semibold text-slate-700">
                <span>Kolom yang disertakan</span>
                <MultiSelect
                    v-model="selectedColumns"
                    :options="configuration.columns"
                    option-label="label"
                    option-value="value"
                    display="chip"
                    filter
                    class="w-full"
                    placeholder="Pilih kolom"
                />
            </label>

            <label class="block space-y-1.5 text-sm font-semibold text-slate-700">
                <span>Cakupan kelengkapan</span>
                <Select v-model="selectedDataset" :options="datasetOptions" option-label="label" option-value="value" class="w-full" />
            </label>

            <details class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                <summary class="cursor-pointer text-sm font-bold text-slate-700">Atur urutan {{ selectedColumns.length }} kolom</summary>
                <ol class="mt-3 max-h-52 space-y-1 overflow-y-auto pr-1">
                    <li v-for="(column, index) in selectedColumns" :key="column" class="flex min-h-11 items-center gap-2 rounded-lg bg-white px-3 py-1.5">
                        <span class="min-w-0 flex-1 truncate text-sm text-slate-700">{{ index + 1 }}. {{ columnLabel(column) }}</span>
                        <button type="button" class="inline-flex size-9 items-center justify-center rounded-md text-slate-500 hover:bg-slate-100 disabled:opacity-30" :disabled="index === 0" :aria-label="`Naikkan ${columnLabel(column)}`" @click="moveColumn(index, -1)"><i class="pi pi-arrow-up" /></button>
                        <button type="button" class="inline-flex size-9 items-center justify-center rounded-md text-slate-500 hover:bg-slate-100 disabled:opacity-30" :disabled="index === selectedColumns.length - 1" :aria-label="`Turunkan ${columnLabel(column)}`" @click="moveColumn(index, 1)"><i class="pi pi-arrow-down" /></button>
                    </li>
                </ol>
            </details>

            <p v-if="['geojson', 'kml'].includes(selectedFormat) && previewWithoutCoordinates > 0" class="rounded-lg bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800">
                {{ formatNumber(previewWithoutCoordinates) }} data tanpa koordinat tidak dapat menjadi titik geospasial. Gunakan “Laporan data bermasalah” untuk mengekspor daftar pengecualiannya.
            </p>

            <div class="rounded-xl border px-4 py-3" :class="previewError ? 'border-red-200 bg-red-50' : queued ? 'border-amber-200 bg-amber-50' : 'border-emerald-200 bg-emerald-50'">
                <p v-if="previewLoading" class="text-sm font-bold text-slate-700">Menghitung jumlah final…</p>
                <p v-else-if="previewError" class="text-sm font-bold text-red-800">{{ previewError }}</p>
                <p v-else class="text-sm font-bold" :class="queued ? 'text-amber-900' : 'text-emerald-900'">
                    {{ formatNumber(exportCount) }} data · {{ queued ? "Diproses melalui antrean" : "Diunduh langsung" }}
                </p>
                <p v-if="!previewError" class="mt-1 text-xs" :class="queued ? 'text-amber-700' : 'text-emerald-700'">
                    Batas proses langsung untuk konfigurasi ini {{ formatNumber(syncLimit) }} data.
                    {{ queued ? "Anda dapat menutup halaman dan kembali melalui riwayat export." : "Unduhan dimulai setelah konfirmasi." }}
                </p>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button label="Batal" severity="secondary" outlined @click="dialogVisible = false" />
                <Button :label="queued ? 'Masukkan Antrean' : 'Export Sekarang'" icon="pi pi-download" :disabled="selectedColumns.length === 0 || previewLoading || Boolean(previewError)" @click="emit('confirmExport', { queued })" />
            </div>
        </template>
    </Dialog>
</template>
