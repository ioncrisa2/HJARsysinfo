<script setup>
import { computed, reactive, ref, watch } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AdminLayout from "../../../Layouts/AdminLayout.vue";
import UiEmptyState from "../../../components/ui/UiEmptyState.vue";
import UiSurface from "../../../components/ui/UiSurface.vue";
import Button from "primevue/button";
import Select from "primevue/select";
import Tag from "primevue/tag";

const props = defineProps({
    query: { type: String, default: "" },
    filters: { type: Object, default: () => ({}) },
    results: { type: Object, default: () => ({ data: [], links: [] }) },
    summary: { type: Object, default: () => ({ raw_total: 0, filtered_total: 0 }) },
    options: { type: Object, default: () => ({ menuGroups: [], menuNames: [], resourceNames: [] }) },
});

const search = ref(props.query ?? "");
const filterState = reactive({
    menu_group: props.filters?.menu_group ?? "",
    menu_name: props.filters?.menu_name ?? "",
    resource_name: props.filters?.resource_name ?? "",
    per_page: props.filters?.per_page ?? 15,
});

const perPageOptions = [10, 15, 25, 50];

const hasQuery = computed(() => search.value.trim() !== "");
const hasFilters = computed(() => {
    return filterState.menu_group !== "" || filterState.menu_name !== "" || filterState.resource_name !== "" || filterState.per_page !== 15;
});

watch(
    () => props.filters,
    (filters) => {
        filterState.menu_group = filters?.menu_group ?? "";
        filterState.menu_name = filters?.menu_name ?? "";
        filterState.resource_name = filters?.resource_name ?? "";
        filterState.per_page = filters?.per_page ?? 15;
    },
    { deep: true },
);

watch(
    () => props.query,
    (value) => {
        search.value = value ?? "";
    },
);

let searchTimeout = null;
watch(search, () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applySearch, 350);
});

const buildParams = () => {
    const params = {};
    const q = search.value.trim();

    if (q) params.q = q;
    if (filterState.menu_group) params.menu_group = filterState.menu_group;
    if (filterState.menu_name) params.menu_name = filterState.menu_name;
    if (filterState.resource_name) params.resource_name = filterState.resource_name;
    if (filterState.per_page !== 15) params.per_page = filterState.per_page;

    return params;
};

function applySearch() {
    router.get("/admin/search", buildParams(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

const handleMenuGroupChange = () => {
    filterState.menu_name = "";
    filterState.resource_name = "";
    applySearch();
};

const handleMenuNameChange = () => {
    filterState.resource_name = "";
    applySearch();
};

const resetFilters = () => {
    filterState.menu_group = "";
    filterState.menu_name = "";
    filterState.resource_name = "";
    filterState.per_page = 15;
    applySearch();
};

const clearSearch = () => {
    search.value = "";
    resetFilters();
};

const detailEntries = (details) => Object.entries(details ?? {});
const formatNumber = (value) => new Intl.NumberFormat("id-ID").format(Number(value ?? 0));
</script>

<template>
    <AdminLayout title="Global Search - Admin">
        <Head title="Global Search - Admin" />

        <div class="mb-5 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <h1 class="text-balance text-2xl font-black text-slate-900">Global Search</h1>
                <p class="mt-1 text-pretty text-sm text-slate-500">
                    Cari users, data pembanding, master data, geo data, dan moderation desk.
                </p>
            </div>

            <div v-if="hasQuery" class="flex items-center gap-2">
                <Tag :value="`${formatNumber(summary.filtered_total)} hasil`" severity="info" />
                <Button label="Clear" icon="pi pi-times" severity="secondary" outlined @click="clearSearch" />
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-12">
            <aside class="lg:col-span-3">
                <UiSurface padding="none" class="overflow-hidden">
                    <div class="border-b border-slate-100 bg-slate-50 px-4 py-4">
                        <p class="text-sm font-bold text-slate-900">Filter Hasil</p>
                        <p class="mt-1 text-pretty text-xs text-slate-500">Persempit hasil berdasarkan area menu.</p>
                    </div>

                    <div class="space-y-4 p-4">
                        <div class="space-y-1.5">
                            <label for="search_menu_group" class="text-xs font-semibold text-slate-600">Menu Group</label>
                            <Select
                                input-id="search_menu_group"
                                v-model="filterState.menu_group"
                                :options="options.menuGroups"
                                option-label="label"
                                option-value="value"
                                placeholder="Semua group"
                                show-clear
                                class="w-full"
                                @change="handleMenuGroupChange"
                            />
                        </div>

                        <div class="space-y-1.5">
                            <label for="search_menu_name" class="text-xs font-semibold text-slate-600">Menu</label>
                            <Select
                                input-id="search_menu_name"
                                v-model="filterState.menu_name"
                                :options="options.menuNames"
                                option-label="label"
                                option-value="value"
                                placeholder="Semua menu"
                                show-clear
                                class="w-full"
                                @change="handleMenuNameChange"
                            />
                        </div>

                        <div class="space-y-1.5">
                            <label for="search_resource_name" class="text-xs font-semibold text-slate-600">Resource</label>
                            <Select
                                input-id="search_resource_name"
                                v-model="filterState.resource_name"
                                :options="options.resourceNames"
                                option-label="label"
                                option-value="value"
                                placeholder="Semua resource"
                                show-clear
                                class="w-full"
                                @change="applySearch"
                            />
                        </div>

                        <div class="space-y-1.5">
                            <label for="search_per_page" class="text-xs font-semibold text-slate-600">Per halaman</label>
                            <Select
                                input-id="search_per_page"
                                v-model="filterState.per_page"
                                :options="perPageOptions"
                                class="w-full"
                                @change="applySearch"
                            />
                        </div>

                        <Button
                            label="Reset Filter"
                            icon="pi pi-filter-slash"
                            severity="secondary"
                            outlined
                            class="w-full"
                            :disabled="!hasFilters"
                            @click="resetFilters"
                        />
                    </div>
                </UiSurface>
            </aside>

            <section class="min-w-0 lg:col-span-9">
                <UiSurface padding="none" class="overflow-hidden">
                    <div class="border-b border-slate-100 bg-slate-50 px-4 py-4">
                        <div class="relative">
                            <label for="global_search_page_input" class="sr-only">Cari semua data aplikasi</label>
                            <i class="pi pi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400" />
                            <input
                                id="global_search_page_input"
                                v-model="search"
                                type="search"
                                class="w-full rounded-lg border border-slate-200 bg-white py-2.5 pl-9 pr-3 text-sm text-slate-900 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                                placeholder="Cari semua data aplikasi..."
                            />
                        </div>
                    </div>

                    <div v-if="!hasQuery" class="p-4">
                        <UiEmptyState
                            title="Mulai pencarian"
                            description="Ketik kata kunci untuk mencari data lintas modul admin."
                            icon="pi pi-search"
                        />
                    </div>

                    <div v-else-if="results.data.length === 0" class="p-4">
                        <UiEmptyState
                            title="Tidak ada hasil"
                            description="Coba kata kunci lain atau reset filter hasil."
                            icon="pi pi-search-minus"
                        >
                            <template #actions>
                                <Button label="Reset Filter" icon="pi pi-filter-slash" size="small" @click="resetFilters" />
                            </template>
                        </UiEmptyState>
                    </div>

                    <div v-else class="divide-y divide-slate-100">
                        <article v-for="(result, index) in results.data" :key="`${result.url}-${index}`" class="p-4 hover:bg-slate-50">
                            <div class="mb-3 flex flex-wrap items-center gap-2">
                                <Tag :value="result.menu_group" severity="secondary" />
                                <Tag :value="result.menu_name" severity="info" />
                                <Tag :value="result.resource_name" severity="warning" />
                            </div>

                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="flex min-w-0 gap-3">
                                    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                                        <i :class="result.icon" />
                                    </div>
                                    <div class="min-w-0">
                                        <h2 class="truncate text-base font-bold text-slate-900" :title="result.title">
                                            {{ result.title }}
                                        </h2>

                                        <dl v-if="detailEntries(result.details).length" class="mt-2 grid gap-x-5 gap-y-1 text-xs text-slate-600 sm:grid-cols-2">
                                            <div v-for="[label, value] in detailEntries(result.details)" :key="label" class="flex min-w-0 gap-2">
                                                <dt class="shrink-0 font-semibold">{{ label }}:</dt>
                                                <dd class="truncate">{{ value }}</dd>
                                            </div>
                                        </dl>
                                    </div>
                                </div>

                                <Link
                                    :href="result.url"
                                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50"
                                >
                                    <i class="pi pi-arrow-right text-[10px]" />
                                    Lihat Data
                                </Link>
                            </div>
                        </article>
                    </div>

                    <div
                        v-if="results.links?.length > 3"
                        class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50 px-4 py-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <p class="ui-tabular text-xs font-semibold text-slate-500">
                            Menampilkan {{ results.from || 0 }}-{{ results.to || 0 }} dari {{ formatNumber(results.total) }} hasil
                        </p>
                        <div class="flex flex-wrap gap-1">
                            <template v-for="(link, index) in results.links" :key="index">
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
            </section>
        </div>
    </AdminLayout>
</template>
