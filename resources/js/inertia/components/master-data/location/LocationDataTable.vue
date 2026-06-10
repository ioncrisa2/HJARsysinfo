<script setup>
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Button from "primevue/button";

const props = defineProps({
    items: { type: Array, required: true },
    loading: { type: Boolean, required: true },
    currentLevel: { type: String, required: true },
});

const emit = defineEmits(["edit", "delete", "drill-down"]);
</script>

<template>
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden flex-1">
        <DataTable 
            :value="items" 
            :loading="loading" 
            responsiveLayout="scroll"
            class="p-datatable-sm"
            :paginator="true"
            :rows="10"
            :rowsPerPageOptions="[10, 20, 50]"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            currentPageReportTemplate="{first} - {last} dari {totalRecords}"
            emptyMessage="Tidak ada data ditemukan."
        >
            <Column field="id" header="Kode" sortable style="width: 15%"></Column>
            <Column field="name" header="Nama" sortable style="width: 55%"></Column>
            <Column header="Aksi" style="width: 30%">
                <template #body="{ data }">
                    <div class="flex items-center gap-2">
                        <Button 
                            icon="pi pi-pencil" 
                            severity="secondary" 
                            text 
                            rounded 
                            aria-label="Edit"
                            @click="emit('edit', data)"
                        />
                        <Button 
                            icon="pi pi-trash" 
                            severity="danger" 
                            text 
                            rounded 
                            aria-label="Hapus"
                            @click="emit('delete', data)"
                        />
                        <Button 
                            v-if="currentLevel !== 'village'"
                            label="Masuk"
                            icon="pi pi-arrow-right" 
                            iconPos="right"
                            severity="primary" 
                            outlined
                            class="rounded-lg text-xs px-3 py-1.5 ml-2"
                            @click="emit('drill-down', data)"
                        />
                    </div>
                </template>
            </Column>
        </DataTable>
    </div>
</template>

<style scoped>
:deep(.p-datatable-wrapper) {
    border-radius: 1rem;
}
:deep(.p-datatable-header) {
    background-color: transparent;
    border: none;
}
:deep(.p-paginator) {
    background-color: transparent;
    border-top: 1px solid #f1f5f9;
}
</style>
