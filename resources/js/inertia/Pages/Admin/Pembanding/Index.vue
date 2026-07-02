<script setup>
import { ref, watch, computed } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AdminLayout from "../../../Layouts/AdminLayout.vue";
import Select from "primevue/select";
import DatePicker from "primevue/datepicker";
import Tag from "primevue/tag";
import PembandingImage from "../../../components/pembanding/PembandingImage.vue";

const props = defineProps({
    records: Object,
    filters: Object,
    options: Object,
    perPage: Number,
    can: { type: Object, default: () => ({}) },
});

const formFilters = ref({
    q: props.filters.q || "",
    province_id: props.filters.province_id || null,
    regency_id: props.filters.regency_id || null,
    district_id: props.filters.district_id || null,
    village_id: props.filters.village_id || null,
    jenis_listing_id: props.filters.jenis_listing_id || null,
    jenis_objek_id: props.filters.jenis_objek_id || null,
    created_by: props.filters.created_by || null,
    dari_tanggal: props.filters.dari_tanggal || null,
    sampai_tanggal: props.filters.sampai_tanggal || null,
});

const itemsPerPage = ref(props.perPage || 10);
const perPageOptions = [10, 25, 50, 100, 250];

const submitFilters = () => {
    router.get("/admin/pembanding", {
        ...formFilters.value,
        per_page: itemsPerPage.value,
    }, { preserveState: true, replace: true });
};

// Reset children when parent changes
watch(() => formFilters.value.province_id, (val) => {
    if (!val) {
        formFilters.value.regency_id = null;
        formFilters.value.district_id = null;
        formFilters.value.village_id = null;
    }
    submitFilters();
});

watch(() => formFilters.value.regency_id, (val) => {
    if (!val) {
        formFilters.value.district_id = null;
        formFilters.value.village_id = null;
    }
    submitFilters();
});

watch(() => formFilters.value.district_id, (val) => {
    if (!val) formFilters.value.village_id = null;
    submitFilters();
});

watch([
    () => formFilters.value.village_id,
    () => formFilters.value.jenis_listing_id,
    () => formFilters.value.jenis_objek_id,
    () => formFilters.value.created_by,
    () => formFilters.value.dari_tanggal,
    () => formFilters.value.sampai_tanggal,
    itemsPerPage
], submitFilters);

let searchTimeout = null;
watch(() => formFilters.value.q, (val) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(submitFilters, 500);
});

const activeFilterCount = computed(() => {
    return Object.entries(formFilters.value).filter(([key, val]) => val !== null && val !== "").length;
});

const resetFilters = () => {
    formFilters.value = {
        q: "",
        province_id: null,
        regency_id: null,
        district_id: null,
        village_id: null,
        jenis_listing_id: null,
        jenis_objek_id: null,
        created_by: null,
        dari_tanggal: null,
        sampai_tanggal: null,
    };
    submitFilters();
};

const deleteRecord = (id) => {
    if (confirm("WARNING: Force delete this property permanently?")) {
        router.delete(`/admin/pembanding/${id}`, { preserveScroll: true });
    }
};

const formatCurrency = (val) => {
    if (!val) return "-";
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val);
};

const pricePeriodLabel = (row) => {
    if (!row?.is_sewa) return null;
    return row.sewa_periode_label || (row.jangka_waktu_sewa && row.satuan_waktu_sewa
        ? `per ${row.jangka_waktu_sewa} ${String(row.satuan_waktu_sewa).toLowerCase()}`
        : "periode sewa belum diisi");
};

const formatDate = (val) => {
    if (!val) return "-";
    return new Date(val).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};
</script>

<template>
    <AdminLayout title="Bank Data — Admin">
        <!-- Header & Top Actions -->
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-slate-900 tracking-tight">Appraisal Data</h2>
                <p class="text-xs text-slate-500 mt-1">Kelola dan telusuri seluruh bank data properti.</p>
            </div>
            
            <Link
                v-if="props.can.create"
                href="/admin/pembanding/create"
                class="inline-flex items-center justify-center gap-2 bg-slate-900 text-white px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-slate-800 transition shadow-sm shadow-slate-200"
            >
                <i class="pi pi-plus text-xs" />
                Tambah Data Pembanding
            </Link>
        </div>

        <!-- Filters Section -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-6 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Search -->
                <div class="relative lg:col-span-2">
                    <i class="pi pi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input
                        v-model="formFilters.q"
                        type="text"
                        placeholder="Cari berdasarkan nama jalan atau alamat..."
                        class="w-full pl-11 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all bg-slate-50/50"
                    />
                </div>

                <!-- Jenis Listing -->
                <Select
                    v-model="formFilters.jenis_listing_id"
                    :options="options.jenisListings"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Semua Jenis Listing"
                    class="w-full text-sm rounded-xl"
                    showClear
                />

                <!-- Jenis Objek -->
                <Select
                    v-model="formFilters.jenis_objek_id"
                    :options="options.jenisObjeks"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Semua Jenis Objek"
                    class="w-full text-sm rounded-xl"
                    showClear
                />

                <!-- Creator -->
                <Select
                    v-model="formFilters.created_by"
                    :options="options.creators"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Diinput Oleh"
                    class="w-full text-sm rounded-xl"
                    showClear
                    filter
                />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 border-t border-slate-100 pt-6">
                <!-- Province -->
                <Select
                    v-model="formFilters.province_id"
                    :options="options.provinces"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Provinsi"
                    class="w-full text-sm rounded-xl"
                    showClear
                    filter
                />

                <!-- Regency -->
                <Select
                    v-model="formFilters.regency_id"
                    :options="options.regencies"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Kota/Kabupaten"
                    class="w-full text-sm rounded-xl"
                    :disabled="!formFilters.province_id"
                    showClear
                    filter
                />

                <!-- District -->
                <Select
                    v-model="formFilters.district_id"
                    :options="options.districts"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Kecamatan"
                    class="w-full text-sm rounded-xl"
                    :disabled="!formFilters.regency_id"
                    showClear
                    filter
                />

                <!-- Village -->
                <Select
                    v-model="formFilters.village_id"
                    :options="options.villages"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Kelurahan/Desa"
                    class="w-full text-sm rounded-xl"
                    :disabled="!formFilters.district_id"
                    showClear
                    filter
                />
            </div>

            <div class="flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 pt-6">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-tight">Tanggal Data:</span>
                        <DatePicker v-model="formFilters.dari_tanggal" placeholder="Dari" class="text-sm" dateFormat="yy-mm-dd" showIcon iconDisplay="input" />
                        <span class="text-slate-300 text-xs">-</span>
                        <DatePicker v-model="formFilters.sampai_tanggal" placeholder="Sampai" class="text-sm" dateFormat="yy-mm-dd" showIcon iconDisplay="input" />
                    </div>
                </div>

                <div v-if="activeFilterCount > 0" class="flex items-center gap-3">
                    <span class="text-xs font-bold text-amber-600 bg-amber-50 px-2.5 py-1 rounded-lg">
                        {{ activeFilterCount }} Filter Aktif
                    </span>
                    <button @click="resetFilters" class="text-xs font-bold text-slate-500 hover:text-slate-900 transition-colors">
                        Reset Semua
                    </button>
                </div>
            </div>
        </div>

        <!-- Table Container -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50/50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                        <tr>
                            <th class="px-6 py-4">Properti</th>
                            <th class="px-6 py-4">Tipe & Luas</th>
                            <th class="px-6 py-4">Harga / Nilai</th>
                            <th v-if="props.can.view || props.can.update || props.can.delete" class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="row in records?.data" :key="row.id" class="hover:bg-slate-50/30 transition-colors group">
                            <!-- Property Details -->
                            <td class="px-6 py-4">
                                <div class="flex items-start gap-4">
                                    <div class="h-16 w-20 rounded-xl bg-slate-100 overflow-hidden border border-slate-200 flex-shrink-0">
                                        <PembandingImage
                                            :src="row.image_url"
                                            :alt="`Foto ${row.alamat_data || 'properti'}`"
                                            placeholder-label="Tidak ada foto"
                                        />
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900 leading-tight mb-1 truncate max-w-md" :title="row.alamat_data">
                                            {{ row.alamat_data || 'Tanpa Alamat' }}
                                        </p>
                                        <p class="text-[11px] text-slate-500 mb-2 truncate max-w-md">{{ row.location }}</p>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded uppercase">ID: #{{ row.id }}</span>
                                            <span class="text-[10px] font-bold text-slate-400 border border-slate-200 px-2 py-0.5 rounded uppercase tracking-tighter">
                                                Input: {{ formatDate(row.created_at) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Type & Area -->
                            <td class="px-6 py-4">
                                <div class="space-y-2">
                                    <div class="flex flex-wrap gap-1.5">
                                        <Tag 
                                            :value="row.jenis_listing?.name" 
                                            :style="{ backgroundColor: row.jenis_listing?.color + '20', color: row.jenis_listing?.color, border: '1px solid ' + row.jenis_listing?.color + '40' }"
                                            class="text-[9px] font-bold uppercase"
                                        />
                                        <Tag 
                                            :value="row.jenis_objek" 
                                            class="bg-slate-100 text-slate-600 border border-slate-200 text-[9px] font-bold uppercase"
                                        />
                                    </div>
                                    <div class="flex items-center gap-3 text-[11px] font-medium text-slate-600">
                                        <span v-if="row.luas_tanah" title="Luas Tanah" class="flex items-center gap-1">
                                            <i class="pi pi-external-link text-[10px] text-slate-400" /> T: {{ row.luas_tanah }}m²
                                        </span>
                                        <span v-if="row.luas_bangunan" title="Luas Bangunan" class="flex items-center gap-1">
                                            <i class="pi pi-home text-[10px] text-slate-400" /> B: {{ row.luas_bangunan }}m²
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- Price -->
                            <td class="px-6 py-4">
                                <div class="space-y-0.5">
                                    <p class="text-sm font-black text-slate-900">{{ formatCurrency(row.harga) }}</p>
                                    <p v-if="row.is_sewa" class="text-[10px] text-amber-600 font-bold uppercase tracking-tight">
                                        {{ pricePeriodLabel(row) }}
                                    </p>
                                    <p v-else-if="row.harga && row.luas_tanah" class="text-[10px] text-slate-400 font-bold uppercase tracking-tight">
                                        {{ formatCurrency(row.harga / row.luas_tanah) }}/m²
                                    </p>
                                </div>
                            </td>

                            <!-- Actions -->
                            <td v-if="props.can.view || props.can.update || props.can.delete" class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1 opacity-40 group-hover:opacity-100 transition-opacity">
                                    <Link
                                        v-if="props.can.view"
                                        :href="`/admin/pembanding/${row.id}`"
                                        class="p-2 text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors"
                                        title="Lihat Detail"
                                    >
                                        <i class="pi pi-eye" />
                                    </Link>
                                    <a 
                                        v-if="row.latitude && row.longitude"
                                        :href="`https://www.google.com/maps?q=${row.latitude},${row.longitude}`" 
                                        target="_blank"
                                        class="p-2 text-slate-500 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                                        title="Buka di Google Maps"
                                    >
                                        <i class="pi pi-map-marker" />
                                    </a>
                                    <Link
                                        v-if="props.can.update"
                                        :href="`/admin/pembanding/${row.id}/edit`"
                                        class="p-2 text-slate-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors"
                                        title="Edit"
                                    >
                                        <i class="pi pi-pencil" />
                                    </Link>
                                    <button
                                        v-if="props.can.delete"
                                        @click="deleteRecord(row.id)"
                                        class="p-2 text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                        title="Hapus"
                                    >
                                        <i class="pi pi-trash" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!records?.data?.length">
                            <td colspan="4" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <i class="pi pi-database text-5xl mb-4 opacity-10" />
                                    <p class="text-lg font-bold text-slate-500">Data Tidak Ditemukan</p>
                                    <p class="text-sm">Coba sesuaikan filter atau kata kunci pencarian Anda.</p>
                                    <button @click="resetFilters" class="mt-4 text-amber-600 font-bold hover:underline">Reset Semua Filter</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Footer & Pagination -->
            <div class="px-8 py-5 border-t border-slate-100 flex flex-col md:flex-row items-center justify-between gap-6 bg-slate-50/50">
                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-tight">Per Page:</span>
                        <Select
                            v-model="itemsPerPage"
                            :options="perPageOptions"
                            class="w-20 text-xs font-bold"
                        />
                    </div>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-widest border-l border-slate-200 pl-6">
                        Menampilkan {{ records.from || 0 }} - {{ records.to || 0 }} Dari {{ records.total }} Data
                    </span>
                </div>
                
                <div class="flex gap-1.5 overflow-x-auto max-w-full">
                    <template v-for="(link, i) in records.links" :key="i">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            class="px-3.5 py-2 text-xs rounded-xl transition-all font-bold"
                            :class="[
                                link.active ? 'bg-slate-900 text-white shadow-lg shadow-slate-300' : 'text-slate-600 hover:bg-white hover:shadow-sm border border-transparent hover:border-slate-200',
                            ]"
                        />
                        <span
                            v-else
                            v-html="link.label"
                            class="px-3.5 py-2 text-xs rounded-xl font-bold opacity-20 cursor-not-allowed border border-transparent"
                            :class="[
                                link.active ? 'bg-slate-900 text-white shadow-lg shadow-slate-300' : 'text-slate-600',
                            ]"
                        />
                    </template>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style>
/* Adjust DatePicker height to match Select */
.p-datepicker input {
    padding: 0.625rem 1rem !important;
    border-radius: 0.75rem !important;
}
</style>
