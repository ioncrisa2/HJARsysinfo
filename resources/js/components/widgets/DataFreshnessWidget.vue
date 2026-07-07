<script setup>
import { computed } from "vue";

const props = defineProps({
    data: {
        type: Object,
        default: () => ({
            basis: "Berdasarkan tanggal_data",
            total: 0,
            with_date: 0,
            missing_date: 0,
            buckets: [],
        }),
    },
});

const formatNumber = (value) => new Intl.NumberFormat("id-ID").format(Number(value ?? 0));

const formatPercent = (value) =>
    `${Number(value ?? 0).toLocaleString("id-ID", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })}%`;

const bucketTone = (color) => {
    if (color === "amber") {
        return {
            bar: "bg-amber-500",
            chip: "bg-amber-50 text-amber-700",
        };
    }

    return {
        bar: "bg-slate-500",
        chip: "bg-slate-100 text-slate-700",
    };
};

const freshnessRate = computed(() => {
    const firstBucket = (props.data?.buckets ?? [])[0];
    return Number(firstBucket?.percentage ?? 0);
});
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                <i class="pi pi-clock text-amber-500 text-xs" />
                Data Freshness
            </div>
            <span class="text-xs text-slate-400">{{ props.data?.basis ?? "Berdasarkan tanggal_data" }}</span>
        </div>

        <div class="space-y-3 p-4">
            <div class="grid gap-2 sm:grid-cols-3">
                <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                    <p class="text-[11px] text-slate-500">Total</p>
                    <p class="ui-tabular text-sm font-semibold text-slate-900">{{ formatNumber(props.data?.total) }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                    <p class="text-[11px] text-slate-500">Ada tanggal</p>
                    <p class="ui-tabular text-sm font-semibold text-slate-900">{{ formatNumber(props.data?.with_date) }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                    <p class="text-[11px] text-slate-500">Tanpa tanggal</p>
                    <p class="ui-tabular text-sm font-semibold text-slate-900">{{ formatNumber(props.data?.missing_date) }}</p>
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-slate-50/60 px-3 py-2">
                <p class="text-[11px] text-slate-500">
                    Freshness 0-30 hari:
                    <span class="font-semibold text-slate-700">{{ formatPercent(freshnessRate) }}</span>
                </p>
            </div>

            <div class="space-y-2.5">
                <div
                    v-for="bucket in props.data?.buckets ?? []"
                    :key="bucket.key"
                    class="rounded-lg border border-slate-200 bg-white px-3 py-2"
                >
                    <div class="mb-1.5 flex items-center justify-between gap-2 text-[11px]">
                        <span class="font-semibold text-slate-700">{{ bucket.label }}</span>
                        <span class="rounded-full px-2 py-0.5 font-semibold" :class="bucketTone(bucket.color).chip">
                            {{ formatNumber(bucket.count) }} data ({{ formatPercent(bucket.percentage) }})
                        </span>
                    </div>
                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                        <div
                            class="h-full rounded-full"
                            :class="bucketTone(bucket.color).bar"
                            :style="{ width: `${Math.max(0, Math.min(Number(bucket.percentage ?? 0), 100))}%` }"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
