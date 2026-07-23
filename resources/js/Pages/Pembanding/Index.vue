<script setup>
import { computed, nextTick, reactive, ref } from "vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";
import { useCascadingLocation } from "../../composables/useCascadingLocation";
import { useDateRangeBridge } from "../../composables/useDateBridge";
import { useDebouncedWatch } from "../../composables/useDebouncedWatch";
import PembandingHeaderBar from "../../components/pembanding/index/PembandingHeaderBar.vue";
import PembandingQuickFilterBar from "../../components/pembanding/index/PembandingQuickFilterBar.vue";
import PembandingFilterPanel from "../../components/pembanding/index/PembandingFilterPanel.vue";
import PembandingResultsPanel from "../../components/pembanding/index/PembandingResultsPanel.vue";
import PembandingExportDialog from "../../components/pembanding/index/PembandingExportDialog.vue";

defineOptions({ layout: AppLayout });

const page = usePage();

const records = computed(() => page.props.records ?? { data: [], links: [], total: 0, from: 0, to: 0 });
const options = computed(() => page.props.options ?? {});
const canCreate = computed(() => Boolean(page.props.can?.create));
const canExport = computed(() => Boolean(page.props.can?.export));
const DEFAULT_PER_PAGE = 16;

const isLoading = ref(false);
const filterDrawerVisible = ref(false);

const makeFilterState = (source = {}) => ({
    province_id: source.province_id ?? null,
    regency_id: source.regency_id ?? null,
    district_id: source.district_id ?? null,
    village_id: source.village_id ?? null,
    q: source.q ?? "",
    dari_tanggal: source.dari_tanggal ?? null,
    sampai_tanggal: source.sampai_tanggal ?? null,
    jenis_listing_id: source.jenis_listing_id ?? null,
    jenis_objek_id: source.jenis_objek_id ?? null,
    created_by: source.created_by ?? null,
    per_page: Number(source.per_page ?? page.props.perPage ?? DEFAULT_PER_PAGE),
});

const appliedFilters = reactive(makeFilterState(page.props.filters));
const filters = reactive(makeFilterState(page.props.filters));

const { dateRange, clearDateRange } = useDateRangeBridge(filters);
const updateDateRange = (value) => { dateRange.value = value; };

const { regencyOptions, districtOptions, villageOptions, locationLoading } = useCascadingLocation(filters, {
    initialOptions: {
        regencies: options.value.regencies ?? [],
        districts: options.value.districts ?? [],
        villages: options.value.villages ?? [],
    },
    preloadOnMounted: true,
    respectInitialOptionsOnPreload: true,
});

const filterIsActive = (source) =>
    Boolean(source.province_id) ||
    Boolean(source.regency_id) ||
    Boolean(source.district_id) ||
    Boolean(source.village_id) ||
    Boolean(source.q?.trim()) ||
    Boolean(source.dari_tanggal) ||
    Boolean(source.sampai_tanggal) ||
    Boolean(source.jenis_listing_id) ||
    Boolean(source.jenis_objek_id) ||
    Boolean(source.created_by);

const hasActiveFilters = computed(() => filterIsActive(appliedFilters));
const hasDraftFilters = computed(() => filterIsActive(filters));

const activeFilterChips = computed(() => {
    const chips = [];
    if (appliedFilters.q?.trim()) chips.push({ key: "q", label: `Kata kunci: ${appliedFilters.q.trim()}` });
    if (appliedFilters.province_id) {
        const found = (options.value.provinces ?? []).find((p) => p.value === appliedFilters.province_id);
        if (found) chips.push({ key: "province_id", label: `Provinsi: ${found.label}` });
    }
    if (appliedFilters.regency_id) {
        const found = (options.value.regencies ?? []).find((r) => r.value === appliedFilters.regency_id);
        if (found) chips.push({ key: "regency_id", label: `Kota: ${found.label}` });
    }
    if (appliedFilters.district_id) {
        const found = (options.value.districts ?? []).find((d) => d.value === appliedFilters.district_id);
        if (found) chips.push({ key: "district_id", label: `Kecamatan: ${found.label}` });
    }
    if (appliedFilters.village_id) {
        const found = (options.value.villages ?? []).find((v) => v.value === appliedFilters.village_id);
        if (found) chips.push({ key: "village_id", label: `Kelurahan: ${found.label}` });
    }
    if (appliedFilters.jenis_listing_id) {
        const found = (options.value.jenisListings ?? []).find((j) => j.value === appliedFilters.jenis_listing_id);
        if (found) chips.push({ key: "jenis_listing_id", label: `Listing: ${found.label}` });
    }
    if (appliedFilters.jenis_objek_id) {
        const found = (options.value.jenisObjeks ?? []).find((j) => j.value === appliedFilters.jenis_objek_id);
        if (found) chips.push({ key: "jenis_objek_id", label: `Objek: ${found.label}` });
    }
    if (appliedFilters.created_by) {
        const found = (options.value.creators ?? []).find((user) => user.value === appliedFilters.created_by);
        if (found) chips.push({ key: "created_by", label: `Input oleh: ${found.label}` });
    }
    if (appliedFilters.dari_tanggal || appliedFilters.sampai_tanggal) {
        chips.push({ key: "date", label: `Tanggal data: ${appliedFilters.dari_tanggal ?? "?"} - ${appliedFilters.sampai_tanggal ?? "?"}` });
    }
    return chips;
});

const confirmVisible = reactive({ filter: false });
const exportMeta = reactive({ format: "excel", count: 0 });

const toQueryPayload = (source = appliedFilters) => {
    const payload = {
        province_id: source.province_id,
        regency_id: source.regency_id,
        district_id: source.district_id,
        village_id: source.village_id,
        q: source.q?.trim() || null,
        dari_tanggal: source.dari_tanggal || null,
        sampai_tanggal: source.sampai_tanggal || null,
        jenis_listing_id: source.jenis_listing_id,
        jenis_objek_id: source.jenis_objek_id,
        created_by: source.created_by,
        per_page: Number(source.per_page) || DEFAULT_PER_PAGE,
    };
    return Object.fromEntries(Object.entries(payload).filter(([, value]) => value !== null && value !== ""));
};

const buildExportUrl = (format = "excel") => {
    const params = new URLSearchParams(toQueryPayload());
    params.set("format", format);
    return `/app/export/download?${params.toString()}&scope=filtered`;
};

const exportByFilter = (format = "excel") => {
    if (!canExport.value) return;

    exportMeta.format = format;
    exportMeta.count = records.value.total ?? 0;
    confirmVisible.filter = true;
};

const doExportFilter = () => {
    confirmVisible.filter = false;
    if (typeof window === "undefined") return;
    window.location.href = buildExportUrl(exportMeta.format);
};

let filterVisitId = 0;
const submitAppliedFilters = () => {
    const visitId = ++filterVisitId;
    isLoading.value = true;
    filterDrawerVisible.value = false;
    router.get("/app/pembanding", toQueryPayload(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => {
            if (visitId === filterVisitId) isLoading.value = false;
        },
    });
};

const syncDraftFromApplied = async () => {
    Object.assign(filters, {
        q: appliedFilters.q,
        dari_tanggal: appliedFilters.dari_tanggal,
        sampai_tanggal: appliedFilters.sampai_tanggal,
        jenis_listing_id: appliedFilters.jenis_listing_id,
        jenis_objek_id: appliedFilters.jenis_objek_id,
        created_by: appliedFilters.created_by,
        per_page: appliedFilters.per_page,
    });

    filters.province_id = appliedFilters.province_id;
    await nextTick();
    filters.regency_id = appliedFilters.regency_id;
    await nextTick();
    filters.district_id = appliedFilters.district_id;
    await nextTick();
    filters.village_id = appliedFilters.village_id;
};

const quickSearch = useDebouncedWatch(
    () => appliedFilters.q,
    () => {
        filters.q = appliedFilters.q;
        submitAppliedFilters();
    },
    { delay: 450 },
);

const submitQuickSearch = () => {
    quickSearch.cancel();
    filters.q = appliedFilters.q;
    submitAppliedFilters();
};

const applyDraftFilters = async () => {
    Object.assign(appliedFilters, makeFilterState(filters));
    await nextTick();
    quickSearch.cancel();
    submitAppliedFilters();
};

const openFilterDrawer = async () => {
    await syncDraftFromApplied();
    filterDrawerVisible.value = true;
};

const applyQuickProvince = () => {
    appliedFilters.regency_id = null;
    appliedFilters.district_id = null;
    appliedFilters.village_id = null;
    filters.province_id = appliedFilters.province_id;
    filters.regency_id = null;
    filters.district_id = null;
    filters.village_id = null;
    submitAppliedFilters();
};

const applyQuickObjectType = () => {
    filters.jenis_objek_id = appliedFilters.jenis_objek_id;
    submitAppliedFilters();
};

const updatePerPage = (value) => {
    appliedFilters.per_page = Number(value) || DEFAULT_PER_PAGE;
    filters.per_page = appliedFilters.per_page;
    submitAppliedFilters();
};

const removeChip = async (key) => {
    if (key === "date") {
        appliedFilters.dari_tanggal = null;
        appliedFilters.sampai_tanggal = null;
    } else {
        appliedFilters[key] = key === "q" ? "" : null;
    }
    if (key === "province_id") {
        appliedFilters.regency_id = null;
        appliedFilters.district_id = null;
        appliedFilters.village_id = null;
    }
    if (key === "regency_id") {
        appliedFilters.district_id = null;
        appliedFilters.village_id = null;
    }
    if (key === "district_id") appliedFilters.village_id = null;
    await syncDraftFromApplied();
    quickSearch.cancel();
    submitAppliedFilters();
};

const resetFilters = async () => {
    Object.assign(appliedFilters, makeFilterState({ per_page: DEFAULT_PER_PAGE }));
    await syncDraftFromApplied();
    clearDateRange();
    quickSearch.cancel();
    submitAppliedFilters();
};
</script>

<template>
    <Head title="Data Pembanding" />

    <div class="space-y-3">

        <PembandingHeaderBar
            :total="records.total ?? 0"
            :active-filter-count="activeFilterChips.length"
            :records="records"
            :can-create="canCreate"
            :can-export="canExport"
            @export-excel="() => exportByFilter('excel')"
            @export-pdf="() => exportByFilter('pdf')"
        >
            <template #quick-filters>
                <PembandingQuickFilterBar
                    :filters="appliedFilters"
                    :options="options"
                    :active-filter-count="activeFilterChips.length"
                    :is-loading="isLoading"
                    @search="submitQuickSearch"
                    @province-change="applyQuickProvince"
                    @object-type-change="applyQuickObjectType"
                    @open-advanced="openFilterDrawer"
                />
            </template>
        </PembandingHeaderBar>

        <!-- Active Filter Chips -->
        <Transition name="chips">
            <div v-if="activeFilterChips.length > 0" class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold text-slate-600">Filter aktif:</span>
                <span
                    v-for="chip in activeFilterChips"
                    :key="chip.key"
                    class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-700 cursor-pointer hover:bg-slate-50 transition-colors group"
                    @click="removeChip(chip.key)"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500" />
                    {{ chip.label }}
                    <i class="pi pi-times text-[10px] text-slate-400 group-hover:text-slate-700 transition-colors" />
                </span>
                <button
                    class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-500 hover:bg-slate-50 hover:text-slate-700 transition-colors"
                    @click="resetFilters"
                >
                    <i class="pi pi-times-circle text-[10px]" />
                    Hapus semua
                </button>
            </div>
        </Transition>

        <PembandingFilterPanel
            :filters="filters"
            :options="options"
            :regency-options="regencyOptions"
            :district-options="districtOptions"
            :village-options="villageOptions"
            :location-loading="locationLoading"
            :has-active-filters="hasDraftFilters"
            :date-range="dateRange"
            :drawer-visible="filterDrawerVisible"
            @submit="applyDraftFilters"
            @reset="resetFilters"
            @update:date-range="updateDateRange"
            @update:drawer-visible="filterDrawerVisible = $event"
        />

        <PembandingResultsPanel
            :records="records"
            :is-loading="isLoading"
            :has-active-filters="hasActiveFilters"
            :per-page="appliedFilters.per_page"
            :per-page-options="options.perPage ?? []"
            @update:per-page="updatePerPage"
            @reset="resetFilters"
        />
    </div>

    <PembandingExportDialog
        v-if="canExport"
        v-model:visible="confirmVisible.filter"
        :count="exportMeta.count"
        :format="exportMeta.format"
        @confirm="doExportFilter"
    />
</template>

<style>
.chips-enter-active, .chips-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.chips-enter-from, .chips-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
