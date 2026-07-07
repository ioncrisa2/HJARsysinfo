<script setup>
import Button from "primevue/button";
import Dropdown from "primevue/dropdown";
import InputText from "primevue/inputtext";

const props = defineProps({
    filterState: { type: Object, required: true },
    canReorder: { type: Boolean, default: false },
    canBulkDelete: { type: Boolean, default: false },
    hasOrderChanges: { type: Boolean, default: false },
    savingOrder: { type: Boolean, default: false },
    deletingBulk: { type: Boolean, default: false },
    selectedIdsLength: { type: Number, default: 0 },
});

const emit = defineEmits([
    "applyFilters",
    "resetFilters",
    "saveOrder",
    "resetOrder",
    "bulkDelete",
]);

const statusOptions = [
    { label: "Semua status", value: "all" },
    { label: "Aktif", value: "active" },
    { label: "Nonaktif", value: "inactive" },
];

const sortOptions = [
    { label: "Urutan", value: "sort_order" },
    { label: "Nama", value: "name" },
    { label: "Slug", value: "slug" },
    { label: "Status", value: "is_active" },
];

const perPageOptions = [10, 20, 50, 100];
</script>

<template>
    <div class="border-b border-slate-100 bg-slate-50 px-4 py-4">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <span class="relative xl:col-span-2">
                <i class="pi pi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400" />
                <InputText
                    v-model="filterState.search"
                    class="w-full pl-9"
                    placeholder="Cari nama atau slug"
                />
            </span>

            <Dropdown
                v-model="filterState.status"
                :options="statusOptions"
                option-label="label"
                option-value="value"
                class="w-full"
                @change="emit('applyFilters')"
            />

            <Dropdown
                v-model="filterState.sort_by"
                :options="sortOptions"
                option-label="label"
                option-value="value"
                class="w-full"
                @change="emit('applyFilters')"
            />

            <div class="grid grid-cols-[1fr_auto] gap-2">
                <Dropdown
                    v-model="filterState.per_page"
                    :options="perPageOptions"
                    class="w-full"
                    @change="emit('applyFilters')"
                />
                <Button
                    :icon="filterState.sort_dir === 'asc' ? 'pi pi-sort-amount-down' : 'pi pi-sort-amount-up'"
                    severity="secondary"
                    outlined
                    aria-label="Ubah arah sort"
                    @click="filterState.sort_dir = filterState.sort_dir === 'asc' ? 'desc' : 'asc'; emit('applyFilters')"
                />
            </div>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-2">
            <Button
                label="Reset Filter"
                icon="pi pi-filter-slash"
                severity="secondary"
                outlined
                size="small"
                @click="emit('resetFilters')"
            />
            <Button
                v-if="canReorder"
                label="Simpan Urutan"
                icon="pi pi-save"
                severity="secondary"
                outlined
                size="small"
                :disabled="!canReorder || !hasOrderChanges"
                :loading="savingOrder"
                @click="emit('saveOrder')"
            />
            <Button
                v-if="canReorder"
                label="Reset Urutan"
                icon="pi pi-undo"
                severity="secondary"
                outlined
                size="small"
                :disabled="!hasOrderChanges"
                @click="emit('resetOrder')"
            />
            <Button
                v-if="canBulkDelete"
                label="Hapus Terpilih"
                icon="pi pi-trash"
                severity="danger"
                outlined
                size="small"
                :disabled="selectedIdsLength === 0"
                :loading="deletingBulk"
                @click="emit('bulkDelete')"
            />
            <span v-if="canReorder === false" class="text-pretty text-xs font-medium text-slate-500">
                Drag urutan aktif saat sort = Urutan dan arah = asc.
            </span>
        </div>
    </div>
</template>
