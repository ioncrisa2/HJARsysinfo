<script setup>
import { computed } from "vue";

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({ total: 0, this_month: 0, last_month: 0, with_coords: 0, province_count: 0 }),
    },
});

const monthTrend = computed(() => {
    const diff = (props.stats.this_month ?? 0) - (props.stats.last_month ?? 0);
    const abs = Math.abs(diff);
    return { diff, abs, positive: diff >= 0 };
});
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="grid divide-y divide-slate-100 sm:grid-cols-4 sm:divide-x sm:divide-y-0">
            <div class="px-4 py-3 sm:px-3 sm:py-2.5">
                <p class="text-xs font-semibold text-slate-600">Total data</p>
                <p class="ui-tabular mt-1 text-2xl font-semibold text-slate-950">{{ stats.total ?? 0 }}</p>
                <p class="mt-1 text-xs text-slate-500">Semua pembanding</p>
            </div>

            <div class="px-4 py-3 sm:px-3 sm:py-2.5">
                <p class="text-xs font-semibold text-slate-600">Input bulan ini</p>
                <p class="ui-tabular mt-1 text-2xl font-semibold text-slate-950">{{ stats.this_month ?? 0 }}</p>
                <p class="mt-1 text-xs text-slate-500">
                    <span class="ui-tabular">{{ monthTrend.abs }}</span>
                    <span class="ml-1">{{ monthTrend.positive ? "lebih banyak" : "lebih sedikit" }}</span>
                    <span class="ml-1">vs bulan lalu</span>
                </p>
            </div>

            <div class="px-4 py-3 sm:px-3 sm:py-2.5">
                <p class="text-xs font-semibold text-slate-600">Dengan koordinat</p>
                <p class="ui-tabular mt-1 text-2xl font-semibold text-slate-950">{{ stats.with_coords ?? 0 }}</p>
                <p class="mt-1 text-xs text-slate-500">Punya lat/lng</p>
            </div>

            <div class="px-4 py-3 sm:px-3 sm:py-2.5">
                <p class="text-xs font-semibold text-slate-600">Provinsi tercakup</p>
                <p class="ui-tabular mt-1 text-2xl font-semibold text-slate-950">{{ stats.province_count ?? 0 }}</p>
                <p class="mt-1 text-xs text-slate-500">Dari 38 provinsi</p>
            </div>
        </div>
    </div>
</template>
