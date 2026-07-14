<script setup>
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Select from "primevue/select";

const props = defineProps({
    filters: { type: Object, required: true },
    options: { type: Object, default: () => ({}) },
    activeFilterCount: { type: Number, default: 0 },
    isLoading: { type: Boolean, default: false },
});

const emit = defineEmits(["search", "provinceChange", "objectTypeChange", "openAdvanced"]);
</script>

<template>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-[minmax(18rem,2fr)_minmax(12rem,1fr)_minmax(12rem,1fr)_auto] xl:items-end">
        <div class="sm:col-span-2 xl:col-span-1">
            <label for="pembanding-quick-search" class="mb-1.5 block text-xs font-semibold text-slate-600">
                Cari alamat
            </label>
            <div class="relative">
                <i class="pi pi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400" aria-hidden="true" />
                <InputText
                    id="pembanding-quick-search"
                    v-model="props.filters.q"
                    class="h-11 w-full !pl-9"
                    placeholder="Contoh: Jl. Merdeka"
                    autocomplete="off"
                    @keydown.enter.prevent="emit('search')"
                />
                <span v-if="props.isLoading" class="absolute right-3 top-1/2 -translate-y-1/2" role="status" aria-label="Memuat hasil">
                    <i class="pi pi-spin pi-spinner text-sm text-amber-600" aria-hidden="true" />
                </span>
            </div>
        </div>

        <div>
            <label for="pembanding-quick-province" class="mb-1.5 block text-xs font-semibold text-slate-600">
                Lokasi
            </label>
            <Select
                v-model="props.filters.province_id"
                input-id="pembanding-quick-province"
                :options="props.options.provinces ?? []"
                option-label="label"
                option-value="value"
                filter
                show-clear
                class="w-full"
                placeholder="Semua provinsi"
                @change="emit('provinceChange')"
            />
        </div>

        <div>
            <label for="pembanding-quick-object" class="mb-1.5 block text-xs font-semibold text-slate-600">
                Jenis objek
            </label>
            <Select
                v-model="props.filters.jenis_objek_id"
                input-id="pembanding-quick-object"
                :options="props.options.jenisObjeks ?? []"
                option-label="label"
                option-value="value"
                filter
                show-clear
                class="w-full"
                placeholder="Semua objek"
                @change="emit('objectTypeChange')"
            />
        </div>

        <Button
            type="button"
            icon="pi pi-sliders-h"
            label="Filter lainnya"
            severity="secondary"
            outlined
            class="!h-11 w-full sm:col-span-2 xl:col-span-1 xl:w-auto"
            :badge="props.activeFilterCount > 0 ? String(props.activeFilterCount) : undefined"
            badge-severity="warn"
            @click="emit('openAdvanced')"
        />
    </div>
</template>
