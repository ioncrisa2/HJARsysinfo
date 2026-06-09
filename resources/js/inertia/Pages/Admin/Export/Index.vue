<script setup>
import { computed, reactive, ref, watch } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AdminLayout from "../../../Layouts/AdminLayout.vue";
import UiEmptyState from "../../../components/ui/UiEmptyState.vue";
import UiSurface from "../../../components/ui/UiSurface.vue";
import Button from "primevue/button";
import DatePicker from "primevue/datepicker";
import Dialog from "primevue/dialog";
import Select from "primevue/select";
import Tag from "primevue/tag";

const props = defineProps({
    records: { type: Object, default: () => ({ data: [], links: [] }) },
    filters: { type: Object, default: () => ({}) },
    options: { type: Object, default: () => ({}) },
    summary: { type: Object, default: () => ({ total: 0, max_export_rows: 5000 }) },
});

const filterState = reactive({
    q: props.filters?.q ?? "",
    province_id: props.filters?.province_id ?? null,
    regency_id: props.filters?.regency_id ?? null,
    district_id: props.filters?.district_id ?? null,
    village_id: props.filters?.village_id ?? null,
    jenis_listing_id: props.filters?.jenis_listing_id ?? null,
    jenis_objek_id: props.filters?.jenis_objek_id ?? null,
    dari_tanggal: props.filters?.dari_tanggal ?? null,
    sampai_tanggal: props.filters?.sampai_tanggal ?? null,
    per_page: props.filters?.per_page ?? 25,
});

const selectedIds = ref([]);
const exportDialog = ref(false);
const pendingFormat = ref("excel");
const pendingScope = ref("selected");

const perPageOptions = [25, 50, 100];

const visibleIds = computed(() => (props.records?.data ?? []).map((record) => record.id));
const allVisibleSelected = computed(() => visibleIds.value.length > 0 && visibleIds.value.every((id) => selectedIds.value.includes(id)));
const hasFilters = computed(() => {
    return [
        filterState.q,
        filterState.province_id,
        filterState.regency_id,
        filterState.district_id,
        filterState.village_id,
        filterState.jenis_listing_id,
        filterState.jenis_objek_id,
        filterState.dari_tanggal,
        filterState.sampai_tanggal,
    ].some((value) => value !== null && value !== "");
});

const exportCount = computed(() => {
    if (pendingScope.value === "selected") return selectedIds.value.length;

    return Math.min(Number(props.summary?.total ?? 0), Number(props.summary?.max_export_rows ?? 5000));
});

watch(
    () => props.records?.data,
    () => {
        selectedIds.value = selectedIds.value.filter((id) => visibleIds.value.includes(id));
    },
    { deep: true },
);

watch(
    () => props.filters,
    (filters) => {
        filterState.q = filters?.q ?? "";
        filterState.province_id = filters?.province_id ?? null;
        filterState.regency_id = filters?.regency_id ?? null;
        filterState.district_id = filters?.district_id ?? null;
        filterState.village_id = filters?.village_id ?? null;
        filterState.jenis_listing_id = filters?.jenis_listing_id ?? null;
        filterState.jenis_objek_id = filters?.jenis_objek_id ?? null;
        filterState.dari_tanggal = filters?.dari_tanggal ?? null;
        filterState.sampai_tanggal = filters?.sampai_tanggal ?? null;
        filterState.per_page = filters?.per_page ?? 25;
    },
    { deep: true },
);

let searchTimeout = null;
watch(
    () => filterState.q,
    () => {
        if (searchTimeout) clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 400);
    },
);

const normalizeDate = (value) => {
    if (!value) return null;
    if (typeof value === "string") return value.slice(0, 10);

    const year = value.getFullYear();
    const month = String(value.getMonth() + 1).padStart(2, "0");
    const day = String(value.getDate()).padStart(2, "0");

    return `${year}-${month}-${day}`;
};

const buildParams = ({ includePagination = true, includeSelection = false, format = null } = {}) => {
    const params = {};
    const search = `${filterState.q ?? ""}`.trim();

    if (search) params.q = search;
    if (filterState.province_id) params.province_id = filterState.province_id;
    if (filterState.regency_id) params.regency_id = filterState.regency_id;
    if (filterState.district_id) params.district_id = filterState.district_id;
    if (filterState.village_id) params.village_id = filterState.village_id;
    if (filterState.jenis_listing_id) params.jenis_listing_id = filterState.jenis_listing_id;
    if (filterState.jenis_objek_id) params.jenis_objek_id = filterState.jenis_objek_id;
    if (normalizeDate(filterState.dari_tanggal)) params.dari_tanggal = normalizeDate(filterState.dari_tanggal);
    if (normalizeDate(filterState.sampai_tanggal)) params.sampai_tanggal = normalizeDate(filterState.sampai_tanggal);
    if (includePagination && filterState.per_page !== 25) params.per_page = filterState.per_page;
    if (includeSelection && selectedIds.value.length > 0) params.ids = selectedIds.value.join(",");
    if (format) params.format = format;

    return params;
};

function applyFilters() {
    router.get("/admin/export", buildParams(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

const handleProvinceChange = () => {
    filterState.regency_id = null;
    filterState.district_id = null;
    filterState.village_id = null;
    applyFilters();
};

const handleRegencyChange = () => {
    filterState.district_id = null;
    filterState.village_id = null;
    applyFilters();
};

const handleDistrictChange = () => {
    filterState.village_id = null;
    applyFilters();
};

const resetFilters = () => {
    filterState.q = "";
    filterState.province_id = null;
    filterState.regency_id = null;
    filterState.district_id = null;
    filterState.village_id = null;
    filterState.jenis_listing_id = null;
    filterState.jenis_objek_id = null;
    filterState.dari_tanggal = null;
    filterState.sampai_tanggal = null;
    filterState.per_page = 25;
    selectedIds.value = [];
    applyFilters();
};

const toggleAllVisible = () => {
    selectedIds.value = allVisibleSelected.value
        ? selectedIds.value.filter((id) => !visibleIds.value.includes(id))
        : [...new Set([...selectedIds.value, ...visibleIds.value])];
};

const toggleSelected = (id) => {
    selectedIds.value = selectedIds.value.includes(id)
        ? selectedIds.value.filter((selectedId) => selectedId !== id)
        : [...selectedIds.value, id];
};

const openExport = (format, scope) => {
    pendingFormat.value = format;
    pendingScope.value = scope;
    exportDialog.value = true;
};

const confirmExport = () => {
    const params = buildParams({
        includePagination: false,
        includeSelection: pendingScope.value === "selected",
        format: pendingFormat.value,
    });

    exportDialog.value = false;
    window.location.href = `/admin/export/download?${new URLSearchParams(params).toString()}`;
};

const formatCurrency = (value) => {
    if (!value) return "-";
    return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(value);
};

const pricePeriodLabel = (record) => {
    if (!record?.is_sewa) return null;
    return record.sewa_periode_label || (record.jangka_waktu_sewa && record.satuan_waktu_sewa
        ? `per ${record.jangka_waktu_sewa} ${String(record.satuan_waktu_sewa).toLowerCase()}`
        : "periode sewa belum diisi");
};

const formatDate = (value) => {
    if (!value) return "-";
    return new Date(value).toLocaleDateString("id-ID", { day: "2-digit", month: "short", year: "numeric" });
};

const formatNumber = (value) => new Intl.NumberFormat("id-ID").format(Number(value ?? 0));
</script>

<template>
    <AdminLayout title="Export Data - Admin">
        <Head title="Export Data Pembanding - Admin" />

        <div class="mb-5 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <h1 class="text-balance text-2xl font-black text-slate-900">Export Data Pembanding</h1>
                <p class="mt-1 text-pretty text-sm text-slate-500">
                    Pilih data dari tabel, atau export seluruh hasil filter sampai {{ formatNumber(summary.max_export_rows) }} baris.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <Button
                    label="Export Excel"
                    icon="pi pi-file-excel"
                    :disabled="selectedIds.length === 0"
                    @click="openExport('excel', 'selected')"
                />
                <Button
                    label="Export PDF"
                    icon="pi pi-file-pdf"
                    severity="secondary"
                    outlined
                    :disabled="selectedIds.length === 0"
                    @click="openExport('pdf', 'selected')"
                />
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1fr_280px]">
            <div class="min-w-0 space-y-5">
                <UiSurface padding="none" class="overflow-hidden">
                    <div class="border-b border-slate-100 bg-slate-50 px-4 py-4">
                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                            <span class="relative md:col-span-2">
                                <i class="pi pi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400" />
                                <input
                                    v-model="filterState.q"
                                    type="text"
                                    class="w-full rounded-lg border border-slate-200 bg-white py-2.5 pl-9 pr-3 text-sm text-slate-900 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                                    placeholder="Cari alamat atau nama pemberi informasi"
                                />
                            </span>

                            <Select
                                v-model="filterState.jenis_listing_id"
                                :options="options.jenisListings"
                                option-label="label"
                                option-value="value"
                                placeholder="Jenis listing"
                                show-clear
                                class="w-full"
                                @change="applyFilters"
                            />

                            <Select
                                v-model="filterState.jenis_objek_id"
                                :options="options.jenisObjeks"
                                option-label="label"
                                option-value="value"
                                placeholder="Jenis objek"
                                show-clear
                                class="w-full"
                                @change="applyFilters"
                            />
                        </div>

                        <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                            <Select
                                v-model="filterState.province_id"
                                :options="options.provinces"
                                option-label="label"
                                option-value="value"
                                placeholder="Provinsi"
                                filter
                                show-clear
                                class="w-full"
                                @change="handleProvinceChange"
                            />

                            <Select
                                v-model="filterState.regency_id"
                                :options="options.regencies"
                                option-label="label"
                                option-value="value"
                                placeholder="Kabupaten / Kota"
                                filter
                                show-clear
                                class="w-full"
                                :disabled="!filterState.province_id"
                                @change="handleRegencyChange"
                            />

                            <Select
                                v-model="filterState.district_id"
                                :options="options.districts"
                                option-label="label"
                                option-value="value"
                                placeholder="Kecamatan"
                                filter
                                show-clear
                                class="w-full"
                                :disabled="!filterState.regency_id"
                                @change="handleDistrictChange"
                            />

                            <Select
                                v-model="filterState.village_id"
                                :options="options.villages"
                                option-label="label"
                                option-value="value"
                                placeholder="Desa / Kelurahan"
                                filter
                                show-clear
                                class="w-full"
                                :disabled="!filterState.district_id"
                                @change="applyFilters"
                            />
                        </div>

                        <div class="mt-3 flex flex-col gap-3 border-t border-slate-100 pt-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex flex-wrap items-center gap-2">
                                <DatePicker
                                    v-model="filterState.dari_tanggal"
                                    placeholder="Dari tanggal"
                                    date-format="yy-mm-dd"
                                    show-icon
                                    icon-display="input"
                                    @date-select="applyFilters"
                                />
                                <DatePicker
                                    v-model="filterState.sampai_tanggal"
                                    placeholder="Sampai tanggal"
                                    date-format="yy-mm-dd"
                                    show-icon
                                    icon-display="input"
                                    @date-select="applyFilters"
                                />
                                <Button
                                    label="Reset Filter"
                                    icon="pi pi-filter-slash"
                                    severity="secondary"
                                    outlined
                                    :disabled="!hasFilters"
                                    @click="resetFilters"
                                />
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold text-slate-500">Per halaman</span>
                                <Select v-model="filterState.per_page" :options="perPageOptions" class="w-24" @change="applyFilters" />
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[900px] text-left text-sm">
                            <thead class="border-b border-slate-100 bg-white text-[11px] font-bold uppercase text-slate-400">
                                <tr>
                                    <th class="w-12 px-5 py-4">
                                        <button
                                            type="button"
                                            class="flex size-5 items-center justify-center rounded border border-slate-300 bg-white"
                                            aria-label="Pilih semua data terlihat"
                                            @click="toggleAllVisible"
                                        >
                                            <i v-if="allVisibleSelected" class="pi pi-check text-[10px] text-slate-700" />
                                        </button>
                                    </th>
                                    <th class="px-5 py-4">Data</th>
                                    <th class="px-5 py-4">Lokasi</th>
                                    <th class="px-5 py-4">Tipe</th>
                                    <th class="px-5 py-4 text-right">Harga</th>
                                    <th class="px-5 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="record in records.data" :key="record.id" class="hover:bg-slate-50">
                                    <td class="px-5 py-4">
                                        <button
                                            type="button"
                                            class="flex size-5 items-center justify-center rounded border border-slate-300 bg-white"
                                            :aria-label="`Pilih data #${record.id}`"
                                            @click="toggleSelected(record.id)"
                                        >
                                            <i v-if="selectedIds.includes(record.id)" class="pi pi-check text-[10px] text-slate-700" />
                                        </button>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex items-start gap-3">
                                            <div class="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                                                <img v-if="record.image_url" :src="record.image_url" alt="" class="size-full object-cover" />
                                                <i v-else class="pi pi-image text-slate-300" />
                                            </div>
                                            <div class="min-w-0">
                                                <p class="max-w-sm truncate font-bold text-slate-900" :title="record.alamat_data">
                                                    {{ record.alamat_data || "Tanpa alamat" }}
                                                </p>
                                                <p class="ui-tabular mt-1 text-xs font-semibold text-slate-500">
                                                    #{{ record.id }} · {{ formatDate(record.tanggal_data) }}
                                                </p>
                                                <p class="mt-1 max-w-sm truncate text-xs text-slate-500">
                                                    {{ record.nama_pemberi_informasi || "Pemberi informasi belum diisi" }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <p class="max-w-xs truncate font-semibold text-slate-700">
                                            {{ [record.village, record.district].filter(Boolean).join(", ") || "-" }}
                                        </p>
                                        <p class="mt-1 max-w-xs truncate text-xs text-slate-500">
                                            {{ [record.regency, record.province].filter(Boolean).join(", ") || "-" }}
                                        </p>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-col items-start gap-1.5">
                                            <Tag v-if="record.jenis_listing" :value="record.jenis_listing" severity="info" />
                                            <Tag v-if="record.jenis_objek" :value="record.jenis_objek" severity="secondary" />
                                            <span class="text-xs font-medium text-slate-500">
                                                LT {{ record.luas_tanah ?? "-" }} / LB {{ record.luas_bangunan ?? "-" }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="ui-tabular px-5 py-4 text-right font-bold text-slate-900">
                                        {{ formatCurrency(record.harga) }}
                                        <p v-if="record.is_sewa" class="mt-1 text-xs font-semibold text-amber-700">
                                            {{ pricePeriodLabel(record) }}
                                        </p>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <Link
                                            :href="`/admin/pembanding/${record.id}`"
                                            class="inline-flex size-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-900"
                                            aria-label="Lihat detail"
                                        >
                                            <i class="pi pi-eye text-sm" />
                                        </Link>
                                    </td>
                                </tr>

                                <tr v-if="records.data.length === 0">
                                    <td colspan="6" class="px-5 py-8">
                                        <UiEmptyState
                                            title="Tidak ada data"
                                            description="Ubah filter untuk menemukan data yang bisa diexport."
                                            icon="pi pi-file-export"
                                        >
                                            <template #actions>
                                                <Button label="Reset Filter" icon="pi pi-filter-slash" size="small" @click="resetFilters" />
                                            </template>
                                        </UiEmptyState>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-if="records.links?.length > 3"
                        class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50 px-4 py-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <p class="ui-tabular text-xs font-semibold text-slate-500">
                            Menampilkan {{ records.from || 0 }}-{{ records.to || 0 }} dari {{ formatNumber(records.total) }} data
                        </p>
                        <div class="flex flex-wrap gap-1">
                            <template v-for="(link, index) in records.links" :key="index">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    class="rounded-lg border px-3 py-1.5 text-xs font-bold"
                                    :class="link.active ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                                    v-html="link.label"
                                />
                                <span
                                    v-else
                                    class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-300"
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                    </div>
                </UiSurface>
            </div>

            <aside class="space-y-4">
                <UiSurface>
                    <p class="text-sm font-bold text-slate-900">Ringkasan Export</p>
                    <dl class="mt-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <dt class="text-xs font-semibold text-slate-500">Hasil filter</dt>
                            <dd class="ui-tabular text-sm font-black text-slate-900">{{ formatNumber(summary.total) }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-xs font-semibold text-slate-500">Terpilih</dt>
                            <dd class="ui-tabular text-sm font-black text-slate-900">{{ formatNumber(selectedIds.length) }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-xs font-semibold text-slate-500">Batas filter</dt>
                            <dd class="ui-tabular text-sm font-black text-slate-900">{{ formatNumber(summary.max_export_rows) }}</dd>
                        </div>
                    </dl>
                </UiSurface>

                <UiSurface>
                    <p class="text-sm font-bold text-slate-900">Bulk Action</p>
                    <div class="mt-4 space-y-2">
                        <Button
                            label="Selected ke Excel"
                            icon="pi pi-file-excel"
                            class="w-full"
                            :disabled="selectedIds.length === 0"
                            @click="openExport('excel', 'selected')"
                        />
                        <Button
                            label="Selected ke PDF"
                            icon="pi pi-file-pdf"
                            severity="secondary"
                            outlined
                            class="w-full"
                            :disabled="selectedIds.length === 0"
                            @click="openExport('pdf', 'selected')"
                        />
                    </div>
                </UiSurface>

                <UiSurface>
                    <p class="text-sm font-bold text-slate-900">Export Hasil Filter</p>
                    <p class="mt-1 text-pretty text-xs text-slate-500">
                        Tanpa pilihan checkbox, export mengikuti filter aktif dan dibatasi {{ formatNumber(summary.max_export_rows) }} baris.
                    </p>
                    <div class="mt-4 space-y-2">
                        <Button
                            label="Filter ke Excel"
                            icon="pi pi-file-excel"
                            severity="secondary"
                            outlined
                            class="w-full"
                            :disabled="summary.total === 0"
                            @click="openExport('excel', 'filtered')"
                        />
                        <Button
                            label="Filter ke PDF"
                            icon="pi pi-file-pdf"
                            severity="secondary"
                            outlined
                            class="w-full"
                            :disabled="summary.total === 0"
                            @click="openExport('pdf', 'filtered')"
                        />
                    </div>
                </UiSurface>
            </aside>
        </div>

        <Dialog
            v-model:visible="exportDialog"
            modal
            :draggable="false"
            header="Konfirmasi Export"
            style="width: min(520px, 100%)"
        >
            <div class="space-y-3">
                <p class="text-pretty text-sm text-slate-700">
                    Export {{ formatNumber(exportCount) }} data ke format {{ pendingFormat === "pdf" ? "PDF" : "Excel" }}?
                </p>
                <p v-if="pendingScope === 'filtered' && summary.total > summary.max_export_rows" class="text-pretty text-xs font-medium text-amber-700">
                    Hasil filter melebihi batas, hanya {{ formatNumber(summary.max_export_rows) }} data terbaru yang akan diexport.
                </p>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button label="Batal" severity="secondary" outlined @click="exportDialog = false" />
                    <Button label="Export" icon="pi pi-download" @click="confirmExport" />
                </div>
            </template>
        </Dialog>
    </AdminLayout>
</template>
