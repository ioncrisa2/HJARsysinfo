<script setup>
import { Head, Link, router, useForm, usePage } from "@inertiajs/vue3";
import Dialog from "primevue/dialog";
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import AppLayout from "../../Layouts/AppLayout.vue";

defineOptions({ layout: AppLayout });

const props = defineProps({
    batch: { type: Object, required: true },
    rows: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    options: { type: Object, default: () => ({}) },
});

const page = usePage();
const selectionProcessing = ref(false);
const bulkProcessing = ref(false);
const bulkConfirming = ref(false);
const bulkField = ref("");
const bulkValue = ref("");
const filterStatus = ref(props.filters.status ?? "");
const filterSelected = ref(props.filters.selected ?? "");
const finalDialogVisible = ref(false);
const retryingRowId = ref(null);
const pollInFlight = ref(false);
const baseUrl = "/app/pembanding-imports";
let pollTimer = null;

const finalizeForm = useForm({ confirmed: false });
const canEdit = computed(() => props.batch.can_edit !== false);
const isProcessing = computed(() => props.batch.status === "processing");
const isFinished = computed(() => ["complete", "completed"].includes(props.batch.status));
const isPartial = computed(() => props.batch.status === "partial");
const isFailed = computed(() => props.batch.status === "failed");
const selectableRows = computed(() => canEdit.value
    ? props.rows.data.filter((row) => !["duplicate", "final_duplicate", "source_already_imported", "imported", "queued", "processing"].includes(row.status))
    : []);
const selectedVisibleRows = computed(() => selectableRows.value.filter((row) => row.is_selected));
const allVisibleSelected = computed(() => selectableRows.value.length > 0 && selectedVisibleRows.value.length === selectableRows.value.length);
const unselectedRows = computed(() => props.batch.unselected_rows ?? Math.max(0, props.batch.total_rows - props.batch.selected_rows));
const processedRows = computed(() => Number(props.batch.imported_rows ?? 0) + Number(props.batch.failed_rows ?? 0));
const progressPercent = computed(() => {
    const selected = Number(props.batch.selected_rows ?? 0);
    return selected > 0 ? Math.min(100, Math.round((processedRows.value / selected) * 100)) : 0;
});
const flashSuccess = computed(() => page.props.flash?.success ?? null);
const flashError = computed(() => page.props.flash?.error ?? null);

const batchMessage = computed(() => {
    if (isProcessing.value) {
        return {
            classes: "border-blue-200 bg-blue-50 text-blue-950",
            icon: "pi-spin pi-spinner",
            title: `${props.batch.selected_rows} data sedang dimasukkan.`,
            body: "Anda boleh meninggalkan halaman ini. Hasilnya tetap tersimpan dan dapat diperiksa kembali nanti.",
        };
    }
    if (isFinished.value) {
        return {
            classes: "border-emerald-200 bg-emerald-50 text-emerald-950",
            icon: "pi-check-circle",
            title: "Semua data yang dipilih sudah berhasil dimasukkan.",
            body: "Data yang berhasil tidak dapat diubah lagi dari halaman unggahan ini.",
        };
    }
    if (isPartial.value) {
        return {
            classes: "border-amber-300 bg-amber-50 text-amber-950",
            icon: "pi-exclamation-triangle",
            title: "Sebagian data berhasil dimasukkan.",
            body: `${props.batch.imported_rows} berhasil dan ${props.batch.failed_rows} perlu diperiksa. Buka alasan pada masing-masing data sebelum mencoba lagi.`,
        };
    }
    if (isFailed.value) {
        return {
            classes: "border-red-200 bg-red-50 text-red-950",
            icon: "pi-times-circle",
            title: "Data belum berhasil dimasukkan.",
            body: "Periksa alasan pada masing-masing data, perbaiki bila perlu, lalu coba proses lagi.",
        };
    }
    return {
        classes: "border-blue-200 bg-blue-50 text-blue-950",
        icon: "pi-info-circle",
        title: "Data pada halaman ini masih berupa draf.",
        body: "Pilih data yang memang ingin digunakan, lalu buka “Lengkapi” untuk memeriksa setiap isian. Data yang sama sengaja tidak dapat dipilih.",
    };
});

const bulkFields = [
    { value: "status_pemberi_informasi_id", label: "Status pemberi informasi", optionsKey: "statusPemberiInfos" },
    { value: "bentuk_tanah_id", label: "Bentuk tanah", optionsKey: "bentukTanahs" },
    { value: "posisi_tanah_id", label: "Posisi tanah", optionsKey: "posisiTanahs" },
    { value: "kondisi_tanah_id", label: "Kondisi tanah", optionsKey: "kondisiTanahs" },
    { value: "topografi_id", label: "Topografi", optionsKey: "topografis" },
    { value: "dokumen_tanah_id", label: "Dokumen tanah", optionsKey: "dokumenTanahs" },
    { value: "peruntukan_id", label: "Peruntukan", optionsKey: "peruntukans" },
];
const selectedBulkField = computed(() => bulkFields.find((field) => field.value === bulkField.value) ?? null);
const bulkValueOptions = computed(() => props.options[selectedBulkField.value?.optionsKey] ?? []);
const selectedBulkValue = computed(() => bulkValueOptions.value.find((option) => String(option.value) === String(bulkValue.value)) ?? null);
const canPrepareBulkApply = computed(() => canEdit.value && props.batch.selected_rows > 0 && bulkField.value && bulkValue.value !== "");

watch(() => props.filters, (filters) => {
    filterStatus.value = filters?.status ?? "";
    filterSelected.value = filters?.selected ?? "";
}, { deep: true });

const statusClass = (status) => ({
    duplicate: "bg-slate-200 text-slate-800",
    needs_confirmation: "bg-orange-100 text-orange-900",
    ready: "bg-emerald-100 text-emerald-900",
    incomplete: "bg-amber-100 text-amber-900",
    queued: "bg-blue-100 text-blue-900",
    processing: "bg-blue-100 text-blue-900",
    imported: "bg-emerald-100 text-emerald-900",
    failed: "bg-red-100 text-red-900",
    invalid: "bg-red-100 text-red-900",
    final_duplicate: "bg-orange-100 text-orange-900",
    source_already_imported: "bg-slate-200 text-slate-800",
}[status] ?? "bg-slate-100 text-slate-800");

const statusIcon = (status) => ({
    duplicate: "pi-copy",
    ready: "pi-check-circle",
    imported: "pi-check-circle",
    queued: "pi-clock",
    processing: "pi-spin pi-spinner",
    failed: "pi-times-circle",
    final_duplicate: "pi-copy",
    source_already_imported: "pi-history",
}[status] ?? "pi-exclamation-circle");

const patchSelection = (payload) => {
    if (!canEdit.value) return;
    selectionProcessing.value = true;
    router.patch(`${baseUrl}/${props.batch.id}/selection`, payload, {
        preserveScroll: true,
        onFinish: () => { selectionProcessing.value = false; },
    });
};

const setRowsSelected = (rowIds, isSelected) => {
    if (!rowIds.length) return;
    patchSelection({ action: "set_rows", row_ids: rowIds, is_selected: isSelected });
};

const toggleRow = (row) => setRowsSelected([row.id], !row.is_selected);
const toggleVisible = () => setRowsSelected(selectableRows.value.map((row) => row.id), !allVisibleSelected.value);
const runSelectionAction = (action) => patchSelection({ action });

const onBulkFieldChange = () => {
    bulkValue.value = "";
    bulkConfirming.value = false;
};

const applyBulkValue = () => {
    if (!canPrepareBulkApply.value || bulkProcessing.value) return;
    bulkProcessing.value = true;
    router.patch(`${baseUrl}/${props.batch.id}/bulk-apply`, {
        field: bulkField.value,
        value: bulkValue.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            bulkConfirming.value = false;
            bulkField.value = "";
            bulkValue.value = "";
        },
        onFinish: () => { bulkProcessing.value = false; },
    });
};

const applyFilters = () => {
    router.get(`${baseUrl}/${props.batch.id}`, {
        status: filterStatus.value || undefined,
        selected: filterSelected.value || undefined,
    }, { preserveState: true, replace: true });
};

const resetFilters = () => {
    filterStatus.value = "";
    filterSelected.value = "";
    applyFilters();
};

const openFinalDialog = () => {
    if (!props.batch.can_finalize) return;
    finalizeForm.clearErrors();
    finalizeForm.confirmed = false;
    finalDialogVisible.value = true;
};

const finalizeImport = () => {
    if (!finalizeForm.confirmed || finalizeForm.processing) return;
    finalizeForm.post(`${baseUrl}/${props.batch.id}/finalize`, {
        preserveScroll: true,
        onSuccess: () => { finalDialogVisible.value = false; },
    });
};

const retryRow = (row) => {
    if (!row.retry_url || retryingRowId.value !== null) return;
    retryingRowId.value = row.id;
    router.post(row.retry_url, {}, {
        preserveScroll: true,
        onFinish: () => { retryingRowId.value = null; },
    });
};

const pollProgress = () => {
    if (!isProcessing.value || pollInFlight.value || document.visibilityState !== "visible") return;
    pollInFlight.value = true;
    router.reload({
        only: ["batch", "rows"],
        preserveState: true,
        preserveScroll: true,
        onFinish: () => { pollInFlight.value = false; },
    });
};

const stopPolling = () => {
    if (pollTimer !== null) window.clearInterval(pollTimer);
    pollTimer = null;
};

const syncPolling = () => {
    stopPolling();
    if (isProcessing.value) pollTimer = window.setInterval(pollProgress, 4000);
};

watch(() => props.batch.status, syncPolling);
onMounted(syncPolling);
onBeforeUnmount(stopPolling);
</script>

<template>
    <Head :title="`Periksa ${props.batch.filename}`" />

    <div class="space-y-5">
        <header>
            <Link :href="baseUrl" class="inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-500">
                <i class="pi pi-arrow-left" aria-hidden="true" /> Riwayat unggahan
            </Link>
            <div class="mt-2">
                <p class="text-xs font-bold uppercase tracking-wider text-amber-700">Data Pembanding</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-900">Periksa data dari Excel</h1>
                <p class="mt-1 text-sm text-slate-600">{{ props.batch.filename }} · Diunggah oleh {{ props.batch.owner }} · {{ props.batch.total_rows }} data ditemukan</p>
            </div>
        </header>

        <div v-if="flashSuccess" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-900" role="status">
            <i class="pi pi-check-circle mr-2" aria-hidden="true" />{{ flashSuccess }}
        </div>
        <div v-if="flashError" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-900" role="alert">
            <i class="pi pi-times-circle mr-2" aria-hidden="true" />{{ flashError }}
        </div>

        <div class="rounded-lg border px-4 py-3 text-sm" :class="batchMessage.classes" role="status" aria-live="polite">
            <div class="flex gap-3">
                <i class="pi mt-0.5" :class="batchMessage.icon" aria-hidden="true" />
                <div>
                    <p class="font-bold">{{ batchMessage.title }}</p>
                    <p class="mt-0.5">{{ batchMessage.body }}</p>
                </div>
            </div>
        </div>

        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5" aria-label="Ringkasan unggahan">
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-3"><p class="text-xs font-semibold text-slate-500">Data ditemukan</p><p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ props.batch.total_rows }}</p></div>
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-3"><p class="text-xs font-semibold text-slate-500">Data dipilih</p><p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ props.batch.selected_rows }}</p></div>
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-3"><p class="text-xs font-semibold text-slate-500">Sudah lengkap</p><p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ props.batch.ready_rows }}</p></div>
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-3"><p class="text-xs font-semibold text-slate-500">Berhasil masuk</p><p class="mt-1 text-2xl font-bold tabular-nums text-emerald-700">{{ props.batch.imported_rows }}</p></div>
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-3"><p class="text-xs font-semibold text-slate-500">Perlu diperbaiki</p><p class="mt-1 text-2xl font-bold tabular-nums text-red-700">{{ props.batch.failed_rows }}</p></div>
        </section>

        <section v-if="isProcessing || isPartial || isFinished || isFailed" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="progress-heading">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 id="progress-heading" class="text-base font-bold text-slate-900">Hasil pemasukan data</h2>
                <span v-if="isProcessing" class="text-xs font-semibold text-blue-800"><i class="pi pi-spin pi-spinner mr-1" aria-hidden="true" />Memperbarui hasil otomatis</span>
            </div>
            <p class="mt-2 text-sm text-slate-700" aria-live="polite">
                {{ props.batch.imported_rows }} berhasil, {{ props.batch.processing_rows }} sedang diproses, dan {{ props.batch.failed_rows }} perlu diperbaiki.
            </p>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200" role="progressbar" aria-label="Kemajuan pemasukan data" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="progressPercent">
                <div class="h-full rounded-full bg-emerald-600 transition-all" :style="{ width: `${progressPercent}%` }" />
            </div>
        </section>

        <section v-if="props.batch.status === 'draft'" class="rounded-xl border border-amber-300 bg-white p-5 shadow-sm" aria-labelledby="final-heading">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-amber-700">Langkah 4</p>
                    <h2 id="final-heading" class="mt-1 text-lg font-bold text-slate-900">Masukkan ke Data Pembanding</h2>
                    <p class="mt-1 text-sm text-slate-600">Hanya data yang dipilih dan sudah lengkap yang akan dimasukkan.</p>
                    <p v-if="!props.batch.can_finalize" class="mt-2 text-sm font-semibold text-amber-900" role="status">
                        <i class="pi pi-exclamation-circle mr-1" aria-hidden="true" />{{ props.batch.finalize_block_reason }}
                    </p>
                </div>
                <button type="button" class="min-h-11 shrink-0 rounded-lg bg-amber-500 px-5 text-sm font-bold text-slate-950 hover:bg-amber-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-600 disabled:cursor-not-allowed disabled:opacity-50" :disabled="!props.batch.can_finalize" @click="openFinalDialog">
                    Periksa dan masukkan data
                </button>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm" aria-labelledby="filter-heading">
            <h2 id="filter-heading" class="text-sm font-bold text-slate-900">Cari data</h2>
            <form class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end" @submit.prevent="applyFilters">
                <label class="flex-1 text-sm font-semibold text-slate-700">Kondisi data
                    <select v-model="filterStatus" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-500">
                        <option value="">Semua kondisi</option><option value="incomplete">Belum lengkap</option><option value="needs_confirmation">Perlu diperiksa</option><option value="invalid">Perlu diperbaiki</option><option value="duplicate">Data sama dalam unggahan</option><option value="final_duplicate">Sudah ada di Data Pembanding</option><option value="source_already_imported">Sumber pernah dimasukkan</option><option value="ready">Sudah lengkap</option><option value="queued">Menunggu diproses</option><option value="processing">Sedang diproses</option><option value="imported">Berhasil dimasukkan</option><option value="failed">Gagal diproses</option>
                    </select>
                </label>
                <label class="flex-1 text-sm font-semibold text-slate-700">Pilihan
                    <select v-model="filterSelected" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-500"><option value="">Semua data</option><option value="1">Hanya yang dipilih</option><option value="0">Hanya yang tidak dipilih</option></select>
                </label>
                <button type="submit" class="min-h-11 rounded-lg bg-slate-900 px-5 text-sm font-bold text-white hover:bg-slate-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500">Tampilkan</button>
                <button type="button" class="min-h-11 rounded-lg px-4 text-sm font-bold text-slate-600 hover:bg-slate-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-500" @click="resetFilters">Hapus saringan</button>
            </form>
        </section>

        <section v-if="canEdit" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm" aria-labelledby="bulk-heading">
            <div class="max-w-4xl">
                <h2 id="bulk-heading" class="text-base font-bold text-slate-900">Isi nilai yang sama untuk data terpilih</h2>
                <p class="mt-1 text-sm text-slate-600">Gunakan hanya jika nilainya memang sama. Alamat, koordinat, ukuran, harga, dan gambar tetap harus diperiksa satu per satu.</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                    <label class="text-sm font-semibold text-slate-700">Isian yang akan diubah
                        <select v-model="bulkField" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-500" @change="onBulkFieldChange"><option value="">Pilih isian</option><option v-for="field in bulkFields" :key="field.value" :value="field.value">{{ field.label }}</option></select>
                    </label>
                    <label class="text-sm font-semibold text-slate-700">Nilai yang digunakan
                        <select v-model="bulkValue" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-500" :disabled="!bulkField" @change="bulkConfirming = false"><option value="">Pilih nilai</option><option v-for="option in bulkValueOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select>
                    </label>
                    <button type="button" class="min-h-11 rounded-lg bg-slate-900 px-5 text-sm font-bold text-white hover:bg-slate-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500 disabled:cursor-not-allowed disabled:opacity-50" :disabled="!canPrepareBulkApply || bulkProcessing" @click="bulkConfirming = true">Periksa perubahan</button>
                </div>
                <p class="mt-2 text-xs font-semibold" :class="props.batch.selected_rows ? 'text-slate-600' : 'text-amber-800'">{{ props.batch.selected_rows ? `${props.batch.selected_rows} data terpilih akan terkena perubahan.` : 'Pilih setidaknya satu data terlebih dahulu.' }}</p>
                <div v-if="bulkConfirming" class="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-4" role="alert">
                    <p class="font-bold text-amber-950">Pastikan nilai ini benar untuk semua data terpilih.</p>
                    <p class="mt-1 text-sm leading-relaxed text-amber-900">“{{ selectedBulkField?.label }}” pada {{ props.batch.selected_rows }} data akan diubah menjadi “{{ selectedBulkValue?.label }}”. Nilai lama pada isian tersebut akan diganti.</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button type="button" class="min-h-11 rounded-lg bg-amber-500 px-4 text-sm font-bold text-slate-950 hover:bg-amber-400 disabled:opacity-50" :disabled="bulkProcessing" @click="applyBulkValue"><i v-if="bulkProcessing" class="pi pi-spin pi-spinner mr-2" aria-hidden="true" />{{ bulkProcessing ? 'Sedang mengubah…' : `Ya, ubah ${props.batch.selected_rows} data` }}</button>
                        <button type="button" class="min-h-11 rounded-lg px-4 text-sm font-bold text-slate-700 hover:bg-amber-100" :disabled="bulkProcessing" @click="bulkConfirming = false">Batalkan</button>
                    </div>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm" aria-labelledby="data-heading">
            <div class="border-b border-slate-200 px-4 py-4 sm:px-5">
                <h2 id="data-heading" class="text-lg font-bold text-slate-900">Data dari Excel</h2>
                <p class="mt-1 text-sm text-slate-600">{{ canEdit ? 'Pilih data yang akan digunakan. Pilihan dapat diubah selama data masih berupa draf.' : 'Lihat hasil setiap data dan tindakan yang masih perlu dilakukan.' }}</p>
                <div v-if="canEdit" class="mt-4 flex flex-wrap items-center gap-2" aria-label="Pilihan cepat">
                    <button type="button" class="min-h-11 rounded-lg border border-slate-300 px-3 text-sm font-bold text-slate-700 hover:bg-slate-50 disabled:opacity-50" :disabled="selectionProcessing" @click="runSelectionAction('select_all')">Pilih semua</button>
                    <button type="button" class="min-h-11 rounded-lg border border-slate-300 px-3 text-sm font-bold text-slate-700 hover:bg-slate-50 disabled:opacity-50" :disabled="selectionProcessing" @click="runSelectionAction('select_ready')">Pilih yang sudah lengkap</button>
                    <button type="button" class="min-h-11 rounded-lg px-3 text-sm font-bold text-red-700 hover:bg-red-50 disabled:opacity-50" :disabled="selectionProcessing" @click="runSelectionAction('clear_all')">Batalkan semua pilihan</button>
                    <span v-if="selectionProcessing" class="text-xs font-semibold text-slate-500" role="status"><i class="pi pi-spin pi-spinner mr-1" aria-hidden="true" />Menyimpan pilihan…</span>
                </div>
            </div>

            <div v-if="props.rows.data.length" class="overflow-x-auto">
                <table class="min-w-[1100px] divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-600"><tr><th v-if="canEdit" class="w-14 px-4 py-3"><input type="checkbox" class="h-5 w-5 rounded border-slate-300 text-amber-600 focus:ring-amber-500" :checked="allVisibleSelected" :disabled="!selectableRows.length || selectionProcessing" aria-label="Pilih semua data yang terlihat" @change="toggleVisible" /></th><th class="px-4 py-3">Baris</th><th class="px-4 py-3">Aset</th><th class="px-4 py-3">Lokasi</th><th class="px-4 py-3">Kondisi</th><th class="px-4 py-3">Yang perlu dilakukan</th><th class="px-4 py-3 text-right">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="row in props.rows.data" :key="row.id" class="align-top hover:bg-slate-50" :class="row.is_selected && canEdit ? 'bg-amber-50/40' : ''">
                            <td v-if="canEdit" class="px-4 py-4"><input type="checkbox" class="h-5 w-5 rounded border-slate-300 text-amber-600 focus:ring-amber-500 disabled:cursor-not-allowed disabled:opacity-40" :checked="row.is_selected" :disabled="['duplicate', 'final_duplicate', 'source_already_imported', 'imported', 'queued', 'processing'].includes(row.status) || selectionProcessing" :aria-label="['duplicate', 'final_duplicate', 'source_already_imported'].includes(row.status) ? `Baris ${row.source_row_number} tidak dapat dipilih karena datanya sudah ada` : `Pilih baris ${row.source_row_number}`" @change="toggleRow(row)" /></td>
                            <td class="px-4 py-4 font-semibold tabular-nums text-slate-700">{{ row.source_row_number }}</td>
                            <td class="px-4 py-4"><p class="font-bold text-slate-900">{{ row.jenis_pembanding }}</p><p class="mt-1 max-w-xs text-slate-600">{{ row.alamat }}</p></td>
                            <td class="px-4 py-4"><p class="max-w-xs text-slate-700">{{ row.location || '-' }}</p></td>
                            <td class="px-4 py-4"><span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold" :class="statusClass(row.status)"><i class="pi" :class="statusIcon(row.status)" aria-hidden="true" />{{ row.status_label }}</span></td>
                            <td class="px-4 py-4">
                                <p v-if="row.last_error" class="text-sm font-semibold" :class="row.status === 'failed' ? 'text-red-800' : 'text-orange-900'">{{ row.last_error }}</p>
                                <ul v-if="row.warnings?.length" class="space-y-1 text-sm text-orange-900"><li v-for="warning in row.warnings" :key="`${row.id}-${warning.field}-${warning.message}`">{{ warning.message }}</li></ul>
                                <p v-if="row.missing_fields?.length" class="mt-2 text-sm text-slate-700"><span class="font-semibold">Wajib dilengkapi:</span> {{ row.missing_fields.slice(0, 5).map(item => item.label).join(', ') }}<span v-if="row.missing_fields.length > 5">, dan {{ row.missing_fields.length - 5 }} lainnya</span>.</p>
                                <p v-if="!row.last_error && !row.warnings?.length && !row.missing_fields?.length && !['queued', 'processing', 'duplicate', 'final_duplicate', 'source_already_imported'].includes(row.status)" class="text-emerald-800">Tidak ada masalah yang ditemukan.</p>
                                <p v-if="['queued', 'processing'].includes(row.status)" class="text-sm text-blue-800">Data ini sedang dimasukkan. Tunggu hasilnya.</p>
                            </td>
                            <td class="px-4 py-4 text-right">
                                <span v-if="row.status === 'duplicate'" class="text-xs font-semibold text-slate-500">Tidak perlu dilengkapi</span>
                                <Link v-else-if="['final_duplicate', 'source_already_imported'].includes(row.status) && row.result_url" :href="row.result_url" class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-bold text-orange-800 hover:bg-orange-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-orange-600">Lihat data yang sudah ada</Link>
                                <span v-else-if="['final_duplicate', 'source_already_imported'].includes(row.status)" class="text-xs font-semibold text-slate-600">Tidak diproses ulang</span>
                                <Link v-else-if="row.status === 'imported' && row.result_url" :href="row.result_url" class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-bold text-emerald-800 hover:bg-emerald-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-600">Lihat data</Link>
                                <span v-else-if="row.status === 'imported'" class="text-xs font-semibold text-emerald-700">Sudah dimasukkan</span>
                                <span v-else-if="['queued', 'processing'].includes(row.status)" class="text-xs font-semibold text-blue-700">Sedang diproses</span>
                                <div v-else-if="row.retry_url" class="flex flex-col items-end gap-1 sm:flex-row sm:justify-end">
                                    <Link v-if="row.edit_url" :href="row.edit_url" class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-bold text-amber-800 hover:bg-amber-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-500">Perbaiki data</Link>
                                    <button type="button" class="min-h-11 rounded-lg border border-slate-300 px-3 text-sm font-bold text-slate-700 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-slate-600 disabled:opacity-50" :disabled="retryingRowId !== null" @click="retryRow(row)"><i v-if="retryingRowId === row.id" class="pi pi-spin pi-spinner mr-1" aria-hidden="true" />{{ retryingRowId === row.id ? 'Mencoba lagi…' : 'Coba proses lagi' }}</button>
                                </div>
                                <Link v-else-if="row.edit_url" :href="row.edit_url" class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-bold text-amber-800 hover:bg-amber-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-500">{{ row.status === 'failed' ? 'Perbaiki data' : 'Lengkapi' }}</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else class="px-5 py-10 text-center"><p class="font-semibold text-slate-800">Tidak ada data yang sesuai</p><p class="mt-1 text-sm text-slate-500">Ubah atau hapus saringan untuk melihat data lainnya.</p></div>
            <nav v-if="props.rows.links?.length > 3" class="flex flex-wrap justify-center gap-1 border-t border-slate-200 px-5 py-4" aria-label="Halaman data unggahan"><Link v-for="link in props.rows.links" :key="link.label" :href="link.url ?? '#'" preserve-scroll class="rounded-md px-3 py-2 text-sm" :class="[link.active ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100', !link.url ? 'pointer-events-none opacity-40' : '']" v-html="link.label" /></nav>
        </section>

        <Dialog v-model:visible="finalDialogVisible" modal :draggable="false" :closable="!finalizeForm.processing" :dismissable-mask="false" :close-on-escape="!finalizeForm.processing" header="Pemeriksaan akhir" style="width: min(640px, 96vw)">
            <div class="space-y-4 text-sm text-slate-700">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3"><p class="text-xs font-semibold text-emerald-800">Akan dimasukkan</p><p class="mt-1 text-2xl font-bold tabular-nums text-emerald-950">{{ props.batch.selected_rows }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3"><p class="text-xs font-semibold text-slate-600">Tidak dipilih</p><p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ unselectedRows }}</p></div>
                </div>
                <div>
                    <p class="font-bold text-slate-900">Periksa sekali lagi sebelum melanjutkan:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5"><li>Harga, luas tanah, dan luas bangunan sesuai dokumen atau informasi sumber.</li><li>Alamat, lokasi, dan koordinat menunjuk aset yang benar.</li><li>Nama serta status pemberi informasi sudah benar.</li><li>Gambar sesuai dengan masing-masing aset.</li></ul>
                </div>
                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 p-3 font-semibold text-amber-950">
                    <input v-model="finalizeForm.confirmed" type="checkbox" class="mt-0.5 h-5 w-5 shrink-0 rounded border-amber-400 text-amber-600 focus:ring-amber-500" />
                    <span>Saya sudah memeriksa data yang dipilih dan memastikan informasinya benar.</span>
                </label>
                <p v-if="finalizeForm.errors.confirmed" class="font-semibold text-red-700" role="alert">{{ finalizeForm.errors.confirmed }}</p>
            </div>
            <template #footer>
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" class="min-h-11 rounded-lg px-4 text-sm font-bold text-slate-700 hover:bg-slate-100" :disabled="finalizeForm.processing" @click="finalDialogVisible = false">Periksa kembali</button>
                    <button type="button" class="min-h-11 rounded-lg bg-amber-500 px-5 text-sm font-bold text-slate-950 hover:bg-amber-400 disabled:cursor-not-allowed disabled:opacity-50" :disabled="!finalizeForm.confirmed || finalizeForm.processing" @click="finalizeImport"><i v-if="finalizeForm.processing" class="pi pi-spin pi-spinner mr-2" aria-hidden="true" />{{ finalizeForm.processing ? 'Memulai proses…' : 'Ya, masukkan data' }}</button>
                </div>
            </template>
        </Dialog>
    </div>
</template>
