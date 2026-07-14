<script setup>
import { router } from "@inertiajs/vue3";
import Button from "primevue/button";
import Tag from "primevue/tag";
import UiSurface from "../ui/UiSurface.vue";

const props = defineProps({
    summary: { type: Object, required: true },
    selectedIdsLength: { type: Number, default: 0 },
    canDownload: { type: Boolean, default: false },
    exportRuns: { type: Array, default: () => [] },
});
const emit = defineEmits(["openExport"]);
const formatNumber = (value) => new Intl.NumberFormat("id-ID").format(Number(value ?? 0));
const formatDate = (value) => value ? new Date(value).toLocaleString("id-ID", { dateStyle: "medium", timeStyle: "short" }) : "-";
const severity = (status) => ({ completed: "success", failed: "danger", processing: "info", pending: "warn", expired: "secondary" }[status] || "secondary");
const retry = (url) => router.post(url, {}, { preserveScroll: true });
</script>

<template>
    <aside class="space-y-4">
        <UiSurface v-if="canDownload">
            <p class="text-sm font-bold text-slate-900">Ringkasan Export</p>
            <dl class="mt-4 space-y-3">
                <div class="flex justify-between"><dt class="text-xs font-semibold text-slate-500">Hasil filter</dt><dd class="ui-tabular text-sm font-black">{{ formatNumber(summary.total) }}</dd></div>
                <div class="flex justify-between"><dt class="text-xs font-semibold text-slate-500">Terpilih</dt><dd class="ui-tabular text-sm font-black">{{ formatNumber(selectedIdsLength) }}</dd></div>
                <div class="flex justify-between"><dt class="text-xs font-semibold text-slate-500">Tanpa koordinat</dt><dd class="ui-tabular text-sm font-black">{{ formatNumber(summary.without_coordinates) }}</dd></div>
                <div class="flex justify-between"><dt class="text-xs font-semibold text-slate-500">Tanpa foto</dt><dd class="ui-tabular text-sm font-black">{{ formatNumber(summary.without_photo) }}</dd></div>
                <div class="flex justify-between"><dt class="text-xs font-semibold text-slate-500">Tanpa harga</dt><dd class="ui-tabular text-sm font-black">{{ formatNumber(summary.without_price) }}</dd></div>
                <div class="flex justify-between"><dt class="text-xs font-semibold text-slate-500">Data lama (&gt;2 tahun)</dt><dd class="ui-tabular text-sm font-black">{{ formatNumber(summary.stale) }}</dd></div>
                <div class="flex justify-between"><dt class="text-xs font-semibold text-slate-500">Referensi nonaktif</dt><dd class="ui-tabular text-sm font-black">{{ formatNumber(summary.inactive_references) }}</dd></div>
            </dl>
        </UiSurface>

        <UiSurface v-if="canDownload">
            <p class="text-sm font-bold text-slate-900">Buat Export</p>
            <div class="mt-4 space-y-2">
                <Button label="Data Terpilih" icon="pi pi-check-square" class="w-full" :disabled="selectedIdsLength === 0" @click="emit('openExport', 'excel', 'selected')" />
                <Button label="Seluruh Hasil Filter" icon="pi pi-filter" severity="secondary" outlined class="w-full" :disabled="summary.total === 0" @click="emit('openExport', 'excel', 'filtered')" />
            </div>
        </UiSurface>

        <UiSurface v-if="exportRuns.length">
            <p class="text-sm font-bold text-slate-900">Riwayat Terbaru</p>
            <div class="mt-4 divide-y divide-slate-100">
                <div v-for="run in exportRuns" :key="run.id" class="py-3 first:pt-0 last:pb-0">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs font-bold uppercase text-slate-700">{{ run.format }} · {{ run.profile }}</p>
                        <Tag :value="run.status" :severity="severity(run.status)" />
                    </div>
                    <p class="mt-1 text-xs text-slate-500">{{ formatNumber(run.total_records) }} data · {{ formatDate(run.created_at) }}</p>
                    <p v-if="run.error" class="mt-1 line-clamp-2 text-xs text-red-600">{{ run.error }}</p>
                    <div class="mt-2 flex gap-2">
                        <a v-if="run.download_url" :href="run.download_url" class="text-xs font-bold text-blue-700 hover:underline">Unduh</a>
                        <button v-if="run.retry_url" type="button" class="text-xs font-bold text-red-700 hover:underline" @click="retry(run.retry_url)">Coba lagi</button>
                    </div>
                </div>
            </div>
        </UiSurface>
    </aside>
</template>
