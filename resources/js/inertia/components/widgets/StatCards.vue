<script setup>
const props = defineProps({
    stats: {
        type: Object,
        default: () => ({ total: 0, this_month: 0, last_month: 0, province_count: 0 }),
    },
});

const monthTrend = computed(() => {
    const diff = (props.stats.this_month ?? 0) - (props.stats.last_month ?? 0);
    return { diff, positive: diff >= 0 };
});
</script>

<script>
import { computed } from "vue";
</script>

<template>
    <div class="grid gap-3 sm:grid-cols-3">

        <!-- Total Data -->
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total Data</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ stats.total ?? 0 }}</p>
                    <p class="mt-1 text-xs text-slate-400">Semua data pembanding</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100">
                    <i class="pi pi-database text-slate-500" />
                </div>
            </div>
        </div>

        <!-- Bulan Ini -->
        <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-500">Bulan Ini</p>
                    <p class="mt-1 text-3xl font-bold text-amber-700">{{ stats.this_month ?? 0 }}</p>
                    <div class="mt-1 flex items-center gap-1 text-xs">
                        <span class="font-semibold" :class="monthTrend.positive ? 'text-emerald-600' : 'text-red-500'">
                            <i :class="`pi ${monthTrend.positive ? 'pi-arrow-up' : 'pi-arrow-down'} text-[10px]`" />
                            {{ Math.abs(monthTrend.diff) }} dari bulan lalu
                        </span>
                    </div>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100">
                    <i class="pi pi-calendar text-amber-600" />
                </div>
            </div>
        </div>

        <!-- Provinsi Tercakup -->
        <div class="rounded-2xl border border-violet-100 bg-violet-50 p-4 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-violet-400">Provinsi Tercakup</p>
                    <p class="mt-1 text-3xl font-bold text-violet-700">{{ stats.province_count ?? 0 }}</p>
                    <p class="mt-1 text-xs text-violet-400">dari 38 provinsi di Indonesia</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-100">
                    <i class="pi pi-map text-violet-500" />
                </div>
            </div>
            <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-violet-100">
                <div
                    class="h-full rounded-full bg-violet-400 transition-all duration-500"
                    :style="{ width: `${Math.min(((stats.province_count ?? 0) / 38) * 100, 100)}%` }"
                />
            </div>
        </div>

    </div>
</template>
