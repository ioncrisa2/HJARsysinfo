<script setup>
import { computed, reactive } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import TopNavLayout from "../../Layouts/TopNavLayout.vue";

defineOptions({ layout: TopNavLayout });

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({
            q: "",
            asset_category: "",
            asset_subtype: "",
            listing_type: "",
            year: "",
            verification_status: "",
        }),
    },
    records: {
        type: Object,
        default: () => ({
            data: [],
        }),
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            verified: 0,
            last_data_date: null,
            vehicle_total: 0,
            heavy_total: 0,
            barge_total: 0,
        }),
    },
    options: {
        type: Object,
        default: () => ({
            asset_categories: [],
            asset_subtypes: [],
            listing_types: [],
            verification_statuses: [],
        }),
    },
    can: {
        type: Object,
        default: () => ({
            create: false,
        }),
    },
});

const filterForm = reactive({
    q: props.filters.q ?? "",
    asset_category: props.filters.asset_category ?? "",
    asset_subtype: props.filters.asset_subtype ?? "",
    listing_type: props.filters.listing_type ?? "",
    year: props.filters.year ?? "",
    verification_status: props.filters.verification_status ?? "",
});

const hasRecords = computed(() => (props.records?.data?.length ?? 0) > 0);

const filteredSubtypeOptions = computed(() => {
    const all = props.options.asset_subtypes ?? [];

    if (!filterForm.asset_category) {
        return all;
    }

    return all.filter((item) => item.category === filterForm.asset_category);
});

const submitFilter = () => {
    router.get("/home/non-properti", filterForm, {
        preserveState: true,
        replace: true,
    });
};

const resetFilter = () => {
    filterForm.q = "";
    filterForm.asset_category = "";
    filterForm.asset_subtype = "";
    filterForm.listing_type = "";
    filterForm.year = "";
    filterForm.verification_status = "";
    submitFilter();
};

const formatCurrency = (value, currency = "IDR") => {
    if (value === null || value === undefined || value === "") return "-";

    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency,
        maximumFractionDigits: 0,
    }).format(Number(value));
};

const categoryLabel = (category) => {
    const found = (props.options.asset_categories ?? []).find((item) => item.value === category);
    return found?.label ?? category ?? "-";
};
</script>

<template>
    <Head title="Bank Data Non Properti" />

    <div class="space-y-4 py-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">Fase 4</p>
                <h1 class="text-xl font-bold text-slate-900">Bank Data Non Properti</h1>
                <p class="text-sm text-slate-500">Simpan dan cari pembanding kendaraan, alat berat, dan tongkang.</p>
            </div>
            <Link
                v-if="props.can.create"
                href="/home/non-properti/create"
                class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600"
            >
                + Tambah Data
            </Link>
        </div>

        <div class="grid gap-3 md:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Total Data</p>
                <p class="mt-1 text-2xl font-semibold text-slate-900">{{ props.stats.total ?? 0 }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Data Terverifikasi</p>
                <p class="mt-1 text-2xl font-semibold text-emerald-600">{{ props.stats.verified ?? 0 }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Tanggal Data Terakhir</p>
                <p class="mt-1 text-base font-semibold text-slate-900">{{ props.stats.last_data_date ?? "-" }}</p>
            </div>
        </div>

        <div class="grid gap-3 md:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Kendaraan</p>
                <p class="mt-1 text-xl font-semibold text-slate-900">{{ props.stats.vehicle_total ?? 0 }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Alat Berat</p>
                <p class="mt-1 text-xl font-semibold text-slate-900">{{ props.stats.heavy_total ?? 0 }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Tongkang</p>
                <p class="mt-1 text-xl font-semibold text-slate-900">{{ props.stats.barge_total ?? 0 }}</p>
            </div>
        </div>

        <form class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm" @submit.prevent="submitFilter">
            <div class="grid gap-3 md:grid-cols-6">
                <input
                    v-model="filterForm.q"
                    type="text"
                    placeholder="Cari kode / merek / model / kota"
                    class="rounded-lg border border-slate-300 px-3 py-2 text-sm"
                />

                <select v-model="filterForm.asset_category" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Semua Kategori</option>
                    <option v-for="item in props.options.asset_categories" :key="item.value" :value="item.value">
                        {{ item.label }}
                    </option>
                </select>

                <select v-model="filterForm.asset_subtype" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Semua Subjenis</option>
                    <option v-for="item in filteredSubtypeOptions" :key="item.value" :value="item.value">
                        {{ item.label }}
                    </option>
                </select>

                <select v-model="filterForm.listing_type" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Semua Listing</option>
                    <option v-for="item in props.options.listing_types" :key="item.value" :value="item.value">
                        {{ item.label }}
                    </option>
                </select>

                <input
                    v-model="filterForm.year"
                    type="number"
                    min="1950"
                    :max="new Date().getFullYear() + 1"
                    placeholder="Tahun"
                    class="rounded-lg border border-slate-300 px-3 py-2 text-sm"
                />

                <select
                    v-model="filterForm.verification_status"
                    class="rounded-lg border border-slate-300 px-3 py-2 text-sm"
                >
                    <option value="">Semua Verifikasi</option>
                    <option v-for="item in props.options.verification_statuses" :key="item.value" :value="item.value">
                        {{ item.label }}
                    </option>
                </select>
            </div>

            <div class="mt-3 flex gap-2">
                <button
                    type="submit"
                    class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900"
                >
                    Terapkan
                </button>
                <button
                    type="button"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700"
                    @click="resetFilter"
                >
                    Reset
                </button>
            </div>
        </form>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold">Kode</th>
                            <th class="px-3 py-2 text-left font-semibold">Kategori</th>
                            <th class="px-3 py-2 text-left font-semibold">Tipe</th>
                            <th class="px-3 py-2 text-left font-semibold">Unit</th>
                            <th class="px-3 py-2 text-left font-semibold">Metrik</th>
                            <th class="px-3 py-2 text-left font-semibold">Kota</th>
                            <th class="px-3 py-2 text-left font-semibold">Harga</th>
                            <th class="px-3 py-2 text-left font-semibold">Tanggal</th>
                            <th class="px-3 py-2 text-left font-semibold">Media</th>
                            <th class="px-3 py-2 text-left font-semibold">Verifikasi</th>
                            <th class="px-3 py-2 text-left font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody v-if="hasRecords">
                        <tr v-for="row in props.records.data" :key="row.id" class="border-t border-slate-100">
                            <td class="px-3 py-2 font-mono text-xs text-slate-700">{{ row.comparable_code }}</td>
                            <td class="px-3 py-2">{{ categoryLabel(row.asset_category) }}</td>
                            <td class="px-3 py-2">{{ row.asset_subtype ?? "-" }}</td>
                            <td class="px-3 py-2">
                                <div class="font-semibold text-slate-900">{{ row.brand }} {{ row.model }}</div>
                                <div class="text-xs text-slate-500">{{ row.variant ?? "-" }} · {{ row.manufacture_year ?? "-" }}</div>
                            </td>
                            <td class="px-3 py-2">{{ row.usage_metric ?? "-" }}</td>
                            <td class="px-3 py-2">{{ row.location_city ?? "-" }}</td>
                            <td class="px-3 py-2">{{ formatCurrency(row.price) }}</td>
                            <td class="px-3 py-2">{{ row.data_date ?? "-" }}</td>
                            <td class="px-3 py-2">{{ row.media_count ?? 0 }}</td>
                            <td class="px-3 py-2">
                                <span class="rounded-full bg-slate-100 px-2 py-1 text-xs text-slate-700">
                                    {{ row.verification_status }}
                                </span>
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex gap-3">
                                    <Link
                                        :href="`/home/non-properti/${row.id}`"
                                        class="text-sm font-semibold text-amber-700 hover:text-amber-800"
                                    >
                                        Detail
                                    </Link>
                                    <Link
                                        :href="`/home/non-properti/${row.id}/history`"
                                        class="text-sm font-semibold text-slate-600 hover:text-slate-800"
                                    >
                                        Riwayat
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tbody v-else>
                        <tr>
                            <td colspan="11" class="px-3 py-8 text-center text-sm text-slate-500">
                                Belum ada data yang cocok.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between border-t border-slate-100 px-3 py-2 text-xs text-slate-500">
                <span>
                    Menampilkan {{ props.records.from ?? 0 }}-{{ props.records.to ?? 0 }} dari
                    {{ props.records.total ?? 0 }}
                </span>
                <div class="flex gap-2">
                    <Link
                        v-if="props.records.prev_page_url"
                        :href="props.records.prev_page_url"
                        class="rounded border border-slate-300 px-2 py-1 text-slate-700"
                    >
                        Sebelumnya
                    </Link>
                    <Link
                        v-if="props.records.next_page_url"
                        :href="props.records.next_page_url"
                        class="rounded border border-slate-300 px-2 py-1 text-slate-700"
                    >
                        Berikutnya
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
