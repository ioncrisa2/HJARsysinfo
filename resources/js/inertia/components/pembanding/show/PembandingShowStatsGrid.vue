<script setup>
import UiSurface from "../../ui/UiSurface.vue";

defineProps({
    price: { type: String, default: "n/a" },
    landArea: { type: String, default: "n/a" },
    buildingArea: { type: String, default: "n/a" },
    dataDate: { type: String, default: "n/a" },
});
</script>

<template>
    <UiSurface class="ui-tabular" padding="none">
        <dl class="grid grid-cols-2 divide-x divide-y divide-slate-100 sm:grid-cols-4">
            <div class="space-y-1 p-4">
                <dt class="text-xs font-medium text-slate-500">Harga</dt>
                <dd class="break-all text-base font-black leading-tight text-slate-900">
                    <span class="text-amber-700">{{ price }}</span>
                </dd>
            </div>

            <div class="space-y-1 p-4">
                <dt class="text-xs font-medium text-slate-500">Luas Tanah</dt>
                <dd class="text-sm font-semibold text-slate-900">{{ landArea }}</dd>
            </div>

            <div class="space-y-1 p-4">
                <dt class="text-xs font-medium text-slate-500">Luas Bangunan</dt>
                <dd class="text-sm font-semibold text-slate-900">{{ buildingArea }}</dd>
            </div>

            <div class="space-y-1 p-4">
                <dt class="text-xs font-medium text-slate-500">Tanggal Data</dt>
                <dd class="text-sm font-semibold text-slate-900">{{ dataDate }}</dd>
            </div>
        </dl>
    </UiSurface>
</template>

