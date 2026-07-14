<script setup>
import { computed, onMounted, onUnmounted, reactive, ref, watch } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";
import UiSurface from "../../components/ui/UiSurface.vue";
import Button from "primevue/button";

import ExportFilter from "../../components/export/ExportFilter.vue";
import ExportTable from "../../components/export/ExportTable.vue";
import ExportSidebar from "../../components/export/ExportSidebar.vue";
import ExportDialog from "../../components/export/ExportDialog.vue";
import { useDebouncedWatch } from "../../composables/useDebouncedWatch";
import { useVisibleSelection } from "../../composables/useVisibleSelection";
import { apiRequest } from "../../utils/apiRequest";

const props = defineProps({
    records: { type: Object, default: () => ({ data: [], links: [] }) },
    filters: { type: Object, default: () => ({}) },
    options: { type: Object, default: () => ({}) },
    summary: { type: Object, default: () => ({ total: 0, max_export_rows: 5000 }) },
    exportConfiguration: { type: Object, default: () => ({ profiles: [], columns: [] }) },
    exportRuns: { type: Array, default: () => [] },
    can: { type: Object, default: () => ({}) },
});

const filterState = reactive({
    q: props.filters?.q ?? "",
    province_id: props.filters?.province_id ?? null,
    regency_id: props.filters?.regency_id ?? null,
    district_id: props.filters?.district_id ?? null,
    village_id: props.filters?.village_id ?? null,
    jenis_listing_id: props.filters?.jenis_listing_id ?? null,
    jenis_objek_id: props.filters?.jenis_objek_id ?? null,
    created_by: props.filters?.created_by ?? null,
    dari_tanggal: props.filters?.dari_tanggal ?? null,
    sampai_tanggal: props.filters?.sampai_tanggal ?? null,
    per_page: props.filters?.per_page ?? 25,
});

const exportDialog = ref(false);
const pendingFormat = ref("excel");
const pendingScope = ref("selected");
const pendingMode = ref("summary");
const pendingProfile = ref("lengkap");
const pendingColumns = ref([]);
const pendingDataset = ref("all");
const previewState = reactive({ count: null, sync_limit: 5000, queued: false, without_coordinates: 0, loading: false, error: null });

const visibleIds = computed(() => (props.records?.data ?? []).map((record) => record.id));
const { selectedIds, allVisibleSelected, toggleSelected, toggleAllVisible, clearSelection } = useVisibleSelection(visibleIds, { pruneOnChange: false });
const hasFilters = computed(() => {
    return [
        filterState.q,
        filterState.province_id,
        filterState.regency_id,
        filterState.district_id,
        filterState.village_id,
        filterState.jenis_listing_id,
        filterState.jenis_objek_id,
        filterState.created_by,
        filterState.dari_tanggal,
        filterState.sampai_tanggal,
    ].some((value) => value !== null && value !== "");
});

const exportCount = computed(() => {
    if (previewState.count !== null) return Number(previewState.count);
    if (pendingScope.value === "selected") return selectedIds.value.length;
    return Number(props.summary?.total ?? 0);
});

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
        filterState.created_by = filters?.created_by ?? null;
        filterState.dari_tanggal = filters?.dari_tanggal ?? null;
        filterState.sampai_tanggal = filters?.sampai_tanggal ?? null;
        filterState.per_page = filters?.per_page ?? 25;
    },
    { deep: true },
);

useDebouncedWatch(() => filterState.q, applyFilters, { delay: 400 });

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
    if (filterState.created_by) params.created_by = filterState.created_by;
    if (normalizeDate(filterState.dari_tanggal)) params.dari_tanggal = normalizeDate(filterState.dari_tanggal);
    if (normalizeDate(filterState.sampai_tanggal)) params.sampai_tanggal = normalizeDate(filterState.sampai_tanggal);
    if (includePagination && filterState.per_page !== 25) params.per_page = filterState.per_page;
    if (includeSelection && selectedIds.value.length > 0) params.ids = selectedIds.value.join(",");
    if (format) params.format = format;

    return params;
};

function applyFilters() {
    clearSelection();
    router.get("/app/export", buildParams(), {
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
    filterState.created_by = null;
    filterState.dari_tanggal = null;
    filterState.sampai_tanggal = null;
    filterState.per_page = 25;
    clearSelection();
    applyFilters();
};

const openExport = (format, scope) => {
    if (!props.can.download) return;

    pendingFormat.value = format;
    pendingScope.value = scope;
    pendingMode.value = "summary";
    pendingProfile.value = format === "pdf" ? "ringkas" : "lengkap";
    pendingColumns.value = props.exportConfiguration.profiles?.find((profile) => profile.value === pendingProfile.value)?.columns ?? [];
    pendingDataset.value = "all";
    previewState.count = null;
    exportDialog.value = true;
    refreshPreview();
};

watch(pendingProfile, (profile) => {
    pendingColumns.value = props.exportConfiguration.profiles?.find((item) => item.value === profile)?.columns ?? [];
});

const buildExportPayload = () => {
    const params = buildParams({
        includePagination: false,
        includeSelection: pendingScope.value === "selected",
        format: pendingFormat.value,
    });

    return {
        ...params,
        scope: pendingScope.value,
        mode: pendingMode.value,
        profile: pendingProfile.value,
        columns: pendingColumns.value,
        dataset: pendingDataset.value,
    };
};

let previewTimer = null;
async function refreshPreview() {
    window.clearTimeout(previewTimer);
    previewTimer = window.setTimeout(async () => {
        previewState.loading = true;
        previewState.error = null;
        try {
            const preview = await apiRequest("/app/export/preview", { method: "POST", body: buildExportPayload() });
            Object.assign(previewState, preview);
        } catch (error) {
            previewState.error = error.message || "Preview export gagal dimuat.";
        } finally {
            previewState.loading = false;
        }
    }, 180);
}

watch([pendingFormat, pendingMode, pendingProfile, pendingColumns, pendingDataset], () => {
    if (exportDialog.value) refreshPreview();
}, { deep: true });

const confirmExport = ({ queued }) => {
    if (!props.can.download) return;

    const params = buildExportPayload();

    exportDialog.value = false;
    if (queued) {
        router.post("/app/export/runs", params, { preserveScroll: true });
        return;
    }

    const query = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
        if (Array.isArray(value)) value.forEach((item) => query.append(`${key}[]`, item));
        else if (value !== null && value !== "") query.set(key, value);
    });
    window.location.href = `/app/export/download?${query.toString()}`;
};

let pollingTimer = null;
onMounted(() => {
    pollingTimer = window.setInterval(() => {
        if (props.exportRuns.some((run) => ["pending", "processing"].includes(run.status))) {
            router.reload({ only: ["exportRuns"], preserveScroll: true, preserveState: true });
        }
    }, 5000);
});
onUnmounted(() => window.clearInterval(pollingTimer));
onUnmounted(() => window.clearTimeout(previewTimer));

const formatNumber = (value) => new Intl.NumberFormat("id-ID").format(Number(value ?? 0));
</script>

<template>
    <AppLayout title="Export Data">
        <Head title="Export Data Pembanding" />

        <div class="mb-5 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <h1 class="text-balance text-2xl font-black text-slate-900">Export Data Pembanding</h1>
                <p class="mt-1 text-pretty text-sm text-slate-500">
                    Pilih data tertentu atau seluruh hasil filter. Export besar otomatis masuk antrean dan dapat diambil dari riwayat.
                </p>
            </div>

            <div v-if="props.can.download" class="flex flex-wrap gap-2">
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
                    <ExportFilter 
                        :filterState="filterState" 
                        :options="options" 
                        :hasFilters="hasFilters" 
                        @applyFilters="applyFilters" 
                        @handleProvinceChange="handleProvinceChange" 
                        @handleRegencyChange="handleRegencyChange" 
                        @handleDistrictChange="handleDistrictChange" 
                        @resetFilters="resetFilters" 
                    />

                    <ExportTable 
                        :records="records" 
                        :selectedIds="selectedIds" 
                        :allVisibleSelected="allVisibleSelected" 
                        :summary="summary" 
                        @toggleAllVisible="toggleAllVisible" 
                        @toggleSelected="toggleSelected" 
                        @resetFilters="resetFilters" 
                    />
                </UiSurface>
            </div>

            <ExportSidebar 
                :summary="summary" 
                :selectedIdsLength="selectedIds.length" 
                :canDownload="props.can.download"
                :exportRuns="exportRuns"
                @openExport="openExport" 
            />
        </div>

        <ExportDialog 
            v-if="props.can.download"
            v-model:visible="exportDialog" 
            v-model:format="pendingFormat"
            v-model:mode="pendingMode"
            v-model:profile="pendingProfile"
            v-model:columns="pendingColumns"
            v-model:dataset="pendingDataset"
            :scope="pendingScope"
            :exportCount="exportCount" 
            :summary="summary" 
            :configuration="exportConfiguration"
            :previewLoading="previewState.loading"
            :previewError="previewState.error"
            :previewWithoutCoordinates="previewState.without_coordinates"
            @confirmExport="confirmExport" 
        />
    </AppLayout>
</template>
