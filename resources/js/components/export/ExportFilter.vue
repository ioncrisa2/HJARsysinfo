<script setup>
import Button from "primevue/button";
import DatePicker from "primevue/datepicker";
import Select from "primevue/select";

const props = defineProps({
    filterState: { type: Object, required: true },
    options: { type: Object, required: true },
    hasFilters: { type: Boolean, default: false },
    perPageOptions: { type: Array, default: () => [25, 50, 100] },
});

const emit = defineEmits([
    "applyFilters",
    "handleProvinceChange",
    "handleRegencyChange",
    "handleDistrictChange",
    "resetFilters",
]);
</script>

<template>
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
                @change="emit('applyFilters')"
            />

            <Select
                v-model="filterState.jenis_objek_id"
                :options="options.jenisObjeks"
                option-label="label"
                option-value="value"
                placeholder="Jenis objek"
                show-clear
                class="w-full"
                @change="emit('applyFilters')"
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
                @change="emit('handleProvinceChange')"
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
                @change="emit('handleRegencyChange')"
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
                @change="emit('handleDistrictChange')"
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
                @change="emit('applyFilters')"
            />
        </div>

        <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <Select
                v-if="options.creators?.length"
                v-model="filterState.created_by"
                :options="options.creators"
                option-label="label"
                option-value="value"
                placeholder="Pembuat data"
                filter
                show-clear
                class="w-full"
                @change="emit('applyFilters')"
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
                    @date-select="emit('applyFilters')"
                />
                <DatePicker
                    v-model="filterState.sampai_tanggal"
                    placeholder="Sampai tanggal"
                    date-format="yy-mm-dd"
                    show-icon
                    icon-display="input"
                    @date-select="emit('applyFilters')"
                />
                <Button
                    label="Reset Filter"
                    icon="pi pi-filter-slash"
                    severity="secondary"
                    outlined
                    :disabled="!hasFilters"
                    @click="emit('resetFilters')"
                />
            </div>

            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold text-slate-500">Per halaman</span>
                <Select v-model="filterState.per_page" :options="perPageOptions" class="w-24" @change="emit('applyFilters')" />
            </div>
        </div>
    </div>
</template>
