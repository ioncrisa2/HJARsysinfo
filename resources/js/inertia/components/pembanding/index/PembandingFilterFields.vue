<script setup>
import { computed } from "vue";
import Button from "primevue/button";
import Calendar from "primevue/calendar";
import InputText from "primevue/inputtext";
import Listbox from "primevue/listbox";
import Select from "primevue/select";

const props = defineProps({
    filters: { type: Object, required: true },
    options: { type: Object, default: () => ({}) },
    regencyOptions: { type: Array, default: () => [] },
    districtOptions: { type: Array, default: () => [] },
    villageOptions: { type: Array, default: () => [] },
    locationLoading: {
        type: Object,
        default: () => ({ regencies: false, districts: false, villages: false }),
    },
    hasActiveFilters: { type: Boolean, default: false },
    dateRange: { default: null },
});

const emit = defineEmits(["submit", "reset", "update:dateRange"]);

const dateRangeModel = computed({
    get: () => props.dateRange,
    set: (value) => emit("update:dateRange", value),
});
</script>

<template>
    <!-- Keyword -->
    <div>
        <label class="mb-1.5 block text-xs font-semibold text-slate-600">
            Nama Jalan / Kata Kunci
        </label>
        <InputText v-model="props.filters.q" class="w-full" placeholder="mis. Jl. Merdeka" />
    </div>

    <hr class="border-slate-100" />

    <!-- Lokasi -->
    <div class="space-y-3">
        <p class="text-xs font-semibold text-slate-600">Lokasi</p>
        <div>
            <label class="mb-1 block text-xs font-semibold text-slate-500">Provinsi</label>
            <Select
                v-model="props.filters.province_id"
                :options="props.options.provinces ?? []"
                option-label="label" option-value="value"
                filter show-clear class="w-full" placeholder="Pilih provinsi"
            />
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold text-slate-500">Kab / Kota</label>
            <Select
                v-model="props.filters.regency_id"
                :options="props.regencyOptions"
                option-label="label" option-value="value"
                filter show-clear class="w-full"
                :loading="props.locationLoading.regencies"
                :disabled="!props.filters.province_id"
                placeholder="Pilih kab/kota"
            />
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold text-slate-500">Kecamatan</label>
            <Select
                v-model="props.filters.district_id"
                :options="props.districtOptions"
                option-label="label" option-value="value"
                filter show-clear class="w-full"
                :loading="props.locationLoading.districts"
                :disabled="!props.filters.regency_id"
                placeholder="Pilih kecamatan"
            />
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold text-slate-500">Kelurahan</label>
            <Select
                v-model="props.filters.village_id"
                :options="props.villageOptions"
                option-label="label" option-value="value"
                filter show-clear class="w-full"
                :loading="props.locationLoading.villages"
                :disabled="!props.filters.district_id"
                placeholder="Pilih kelurahan"
            />
        </div>
    </div>

    <hr class="border-slate-100" />

    <!-- Jenis -->
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="mb-1 block text-xs font-semibold text-slate-500">Jenis Listing</label>
            <Select
                v-model="props.filters.jenis_listing_id"
                :options="props.options.jenisListings ?? []"
                option-label="label" option-value="value"
                filter show-clear class="w-full" placeholder="Semua"
            />
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold text-slate-500">Jenis Objek</label>
            <Select
                v-model="props.filters.jenis_objek_id"
                :options="props.options.jenisObjeks ?? []"
                option-label="label" option-value="value"
                filter show-clear class="w-full" placeholder="Semua"
            />
        </div>
    </div>

    <hr class="border-slate-100" />

    <!-- Date range -->
    <div>
        <label class="mb-1.5 block text-xs font-semibold text-slate-600">
            Rentang Tanggal
        </label>
        <Calendar
            v-model="dateRangeModel"
            selection-mode="range"
            show-icon icon-display="input"
            :manual-input="false"
            date-format="yy-mm-dd"
            class="w-full" input-class="w-full"
            placeholder="Pilih rentang tanggal"
        />
    </div>

    <hr class="border-slate-100" />

    <!-- Per page -->
    <div>
        <label class="mb-1.5 block text-xs font-semibold text-slate-600">
            Data Per Halaman
        </label>
        <Listbox
            v-model="props.filters.per_page"
            :options="props.options.perPage ?? []"
            option-label="label" option-value="value"
            class="w-full"
        />
    </div>

    <!-- Actions -->
    <div class="flex gap-2 pt-1">
        <Button type="submit" icon="pi pi-filter" label="Terapkan" class="flex-1" @click="emit('submit')" />
        <Button
            type="button" icon="pi pi-refresh" label="Reset"
            severity="secondary" outlined class="flex-1"
            :disabled="!props.hasActiveFilters"
            @click="emit('reset')"
        />
    </div>
</template>
