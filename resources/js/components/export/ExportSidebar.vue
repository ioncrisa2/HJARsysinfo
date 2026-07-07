<script setup>
import Button from "primevue/button";
import UiSurface from "../ui/UiSurface.vue";

const props = defineProps({
    summary: { type: Object, required: true },
    selectedIdsLength: { type: Number, default: 0 },
    canDownload: { type: Boolean, default: false },
});

const emit = defineEmits(["openExport"]);

const formatNumber = (value) => new Intl.NumberFormat("id-ID").format(Number(value ?? 0));
</script>

<template>
    <aside class="space-y-4">
        <UiSurface v-if="canDownload">
            <p class="text-sm font-bold text-slate-900">Ringkasan Export</p>
            <dl class="mt-4 space-y-3">
                <div class="flex items-center justify-between">
                    <dt class="text-xs font-semibold text-slate-500">Hasil filter</dt>
                    <dd class="ui-tabular text-sm font-black text-slate-900">{{ formatNumber(summary.total) }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-xs font-semibold text-slate-500">Terpilih</dt>
                    <dd class="ui-tabular text-sm font-black text-slate-900">{{ formatNumber(selectedIdsLength) }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-xs font-semibold text-slate-500">Batas filter</dt>
                    <dd class="ui-tabular text-sm font-black text-slate-900">{{ formatNumber(summary.max_export_rows) }}</dd>
                </div>
            </dl>
        </UiSurface>

        <UiSurface v-if="canDownload">
            <p class="text-sm font-bold text-slate-900">Bulk Action</p>
            <div class="mt-4 space-y-2">
                <Button
                    label="Selected ke Excel"
                    icon="pi pi-file-excel"
                    class="w-full"
                    :disabled="selectedIdsLength === 0"
                    @click="emit('openExport', 'excel', 'selected')"
                />
                <Button
                    label="Selected ke PDF"
                    icon="pi pi-file-pdf"
                    severity="secondary"
                    outlined
                    class="w-full"
                    :disabled="selectedIdsLength === 0"
                    @click="emit('openExport', 'pdf', 'selected')"
                />
            </div>
        </UiSurface>

        <UiSurface>
            <p class="text-sm font-bold text-slate-900">Export Hasil Filter</p>
            <p class="mt-1 text-pretty text-xs text-slate-500">
                Tanpa pilihan checkbox, export mengikuti filter aktif dan dibatasi {{ formatNumber(summary.max_export_rows) }} baris.
            </p>
            <div class="mt-4 space-y-2">
                <Button
                    label="Filter ke Excel"
                    icon="pi pi-file-excel"
                    severity="secondary"
                    outlined
                    class="w-full"
                    :disabled="summary.total === 0"
                    @click="emit('openExport', 'excel', 'filtered')"
                />
                <Button
                    label="Filter ke PDF"
                    icon="pi pi-file-pdf"
                    severity="secondary"
                    outlined
                    class="w-full"
                    :disabled="summary.total === 0"
                    @click="emit('openExport', 'pdf', 'filtered')"
                />
            </div>
        </UiSurface>
    </aside>
</template>
