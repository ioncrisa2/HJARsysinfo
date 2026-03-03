<script setup>
import { computed, reactive, ref } from "vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import TopNavLayout from "../../Layouts/TopNavLayout.vue";
import { useCascadingLocation } from "../../composables/useCascadingLocation";
import { useDateRangeBridge } from "../../composables/useDateBridge";
import PembandingHeaderBar from "../../components/pembanding/index/PembandingHeaderBar.vue";
import PembandingFilterPanel from "../../components/pembanding/index/PembandingFilterPanel.vue";
import PembandingResultsPanel from "../../components/pembanding/index/PembandingResultsPanel.vue";
import PembandingExportDialog from "../../components/pembanding/index/PembandingExportDialog.vue";

defineOptions({ layout: TopNavLayout });

const page = usePage();

const records = computed(() => page.props.records ?? { data: [], links: [], total: 0, from: 0, to: 0 });
const options = computed(() => page.props.options ?? {});
const DEFAULT_PER_PAGE = 16;

const isLoading = ref(false);
const filterDrawerVisible = ref(false);

const filters = reactive({
    province_id: page.props.filters?.province_id ?? null,
    regency_id: page.props.filters?.regency_id ?? null,
    district_id: page.props.filters?.district_id ?? null,
    village_id: page.props.filters?.village_id ?? null,
    q: page.props.filters?.q ?? "",
    dari_tanggal: page.props.filters?.dari_tanggal ?? null,
    sampai_tanggal: page.props.filters?.sampai_tanggal ?? null,
    jenis_listing_id: page.props.filters?.jenis_listing_id ?? null,
    jenis_objek_id: page.props.filters?.jenis_objek_id ?? null,
    per_page: Number(page.props.perPage ?? DEFAULT_PER_PAGE),
});

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

const hasActiveFilters = computed(() =>
    Boolean(filters.province_id) ||
    Boolean(filters.regency_id) ||
    Boolean(filters.district_id) ||
    Boolean(filters.village_id) ||
    Boolean(filters.q?.trim()) ||
    Boolean(filters.dari_tanggal) ||
    Boolean(filters.sampai_tanggal) ||
    Boolean(filters.jenis_listing_id) ||
    Boolean(filters.jenis_objek_id) ||
    Number(filters.per_page) !== DEFAULT_PER_PAGE
);

const activeFilterChips = computed(() => {
    const chips = [];
    if (filters.q?.trim()) chips.push({ key: "q", label: `Kata kunci: ${filters.q.trim()}` });
    if (filters.province_id) {
        const found = (options.value.provinces ?? []).find((p) => p.value === filters.province_id);
        if (found) chips.push({ key: "province_id", label: `Provinsi: ${found.label}` });
    }
    if (filters.regency_id) {
        const found = regencyOptions.value.find((r) => r.value === filters.regency_id);
        if (found) chips.push({ key: "regency_id", label: `Kota: ${found.label}` });
    }
    if (filters.district_id) {
        const found = districtOptions.value.find((d) => d.value === filters.district_id);
        if (found) chips.push({ key: "district_id", label: `Kecamatan: ${found.label}` });
    }
    if (filters.village_id) {
        const found = villageOptions.value.find((v) => v.value === filters.village_id);
        if (found) chips.push({ key: "village_id", label: `Kelurahan: ${found.label}` });
    }
    if (filters.jenis_listing_id) {
        const found = (options.value.jenisListings ?? []).find((j) => j.value === filters.jenis_listing_id);
        if (found) chips.push({ key: "jenis_listing_id", label: `Listing: ${found.label}` });
    }
    if (filters.jenis_objek_id) {
        const found = (options.value.jenisObjeks ?? []).find((j) => j.value === filters.jenis_objek_id);
        if (found) chips.push({ key: "jenis_objek_id", label: `Objek: ${found.label}` });
    }
    if (filters.dari_tanggal || filters.sampai_tanggal) {
        chips.push({ key: "date", label: `Tanggal: ${filters.dari_tanggal ?? "?"} – ${filters.sampai_tanggal ?? "?"}` });
    }
    return chips;
});

const removeChip = (key) => {
    if (key === "date") { clearDateRange(); filters.dari_tanggal = null; filters.sampai_tanggal = null; }
    else filters[key] = null;
    if (key === "province_id") { filters.regency_id = null; filters.district_id = null; filters.village_id = null; }
    if (key === "regency_id") { filters.district_id = null; filters.village_id = null; }
    if (key === "district_id") filters.village_id = null;
    submitFilters();
};

const confirmVisible = reactive({ filter: false });
const exportMeta = reactive({ format: "excel", count: 0 });

const toQueryPayload = () => {
    const payload = {
        province_id: filters.province_id,
        regency_id: filters.regency_id,
        district_id: filters.district_id,
        village_id: filters.village_id,
        q: filters.q?.trim() || null,
        dari_tanggal: filters.dari_tanggal || null,
        sampai_tanggal: filters.sampai_tanggal || null,
        jenis_listing_id: filters.jenis_listing_id,
        jenis_objek_id: filters.jenis_objek_id,
        per_page: Number(filters.per_page) || DEFAULT_PER_PAGE,
    };
    return Object.fromEntries(Object.entries(payload).filter(([, value]) => value !== null && value !== ""));
};

const buildExportUrl = (format = "excel") => {
    const params = new URLSearchParams(toQueryPayload());
    params.set("format", format);
    return `/home/pembanding/export?${params.toString()}`;
};

const exportByFilter = (format = "excel") => {
    exportMeta.format = format;
    exportMeta.count = records.value.total ?? 0;
    confirmVisible.filter = true;
};

const doExportFilter = () => {
    confirmVisible.filter = false;
    if (typeof window === "undefined") return;
    window.location.href = buildExportUrl(exportMeta.format);
};

const submitFilters = () => {
    isLoading.value = true;
    filterDrawerVisible.value = false;
    router.get("/home/pembanding", toQueryPayload(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => { isLoading.value = false; },
    });
};

const resetFilters = () => {
    filters.province_id = null; filters.regency_id = null;
    filters.district_id = null; filters.village_id = null;
    filters.q = ""; clearDateRange();
    filters.dari_tanggal = null; filters.sampai_tanggal = null;
    filters.jenis_listing_id = null; filters.jenis_objek_id = null;
    filters.per_page = DEFAULT_PER_PAGE;
    submitFilters();
};
</script>

<template>
    <Head title="Data Pembanding" />

    <main class="space-y-4">

        <PembandingHeaderBar
            :total="records.total ?? 0"
            :active-filter-count="activeFilterChips.length"
            :records="records"
            @export-excel="() => exportByFilter('excel')"
            @export-pdf="() => exportByFilter('pdf')"
            @open-filter-drawer="filterDrawerVisible = true"
        />

        <!-- Active Filter Chips -->
        <Transition name="chips">
            <div v-if="activeFilterChips.length > 0" class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Filter aktif:</span>
                <span
                    v-for="chip in activeFilterChips"
                    :key="chip.key"
                    class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 cursor-pointer hover:bg-amber-100 transition-colors group"
                    @click="removeChip(chip.key)"
                >
                    {{ chip.label }}
                    <i class="pi pi-times text-[10px] text-amber-400 group-hover:text-amber-700 transition-colors" />
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
            :has-active-filters="hasActiveFilters"
            :date-range="dateRange"
            :drawer-visible="filterDrawerVisible"
            @submit="submitFilters"
            @reset="resetFilters"
            @update:date-range="updateDateRange"
            @update:drawer-visible="filterDrawerVisible = $event"
        />

        <PembandingResultsPanel
            :records="records"
            :is-loading="isLoading"
            :has-active-filters="hasActiveFilters"
            @reset="resetFilters"
        />
    </main>

    <PembandingExportDialog
        v-model:visible="confirmVisible.filter"
        :count="exportMeta.count"
        :format="exportMeta.format"
        @confirm="doExportFilter"
    />
</template>

<style>
.chips-enter-active, .chips-leave-active { transition: all 0.2s ease; }
.chips-enter-from, .chips-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
