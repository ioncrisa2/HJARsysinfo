<script setup>
import { computed } from "vue";
import Button from "primevue/button";
import Dialog from "primevue/dialog";

const props = defineProps({
    visible: { type: Boolean, required: true },
    pendingFormat: { type: String, default: "excel" },
    pendingScope: { type: String, default: "selected" },
    exportCount: { type: Number, default: 0 },
    summary: { type: Object, required: true },
});

const emit = defineEmits(["update:visible", "confirmExport"]);

const dialogVisible = computed({
    get: () => props.visible,
    set: (val) => emit("update:visible", val),
});

const formatNumber = (value) => new Intl.NumberFormat("id-ID").format(Number(value ?? 0));
</script>

<template>
    <Dialog
        v-model:visible="dialogVisible"
        modal
        :draggable="false"
        header="Konfirmasi Export"
        style="width: min(520px, 100%)"
    >
        <div class="space-y-3">
            <p class="text-pretty text-sm text-slate-700">
                Export {{ formatNumber(exportCount) }} data ke format {{ pendingFormat === "pdf" ? "PDF" : "Excel" }}?
            </p>
            <p v-if="pendingScope === 'filtered' && summary.total > summary.max_export_rows" class="text-pretty text-xs font-medium text-amber-700">
                Hasil filter melebihi batas, hanya {{ formatNumber(summary.max_export_rows) }} data terbaru yang akan diexport.
            </p>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button label="Batal" severity="secondary" outlined @click="dialogVisible = false" />
                <Button label="Export" icon="pi pi-download" @click="emit('confirmExport')" />
            </div>
        </template>
    </Dialog>
</template>
