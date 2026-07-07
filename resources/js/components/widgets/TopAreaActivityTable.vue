<script setup>
const props = defineProps({
    data: {
        type: Object,
        default: () => ({
            period_label: "30 hari terakhir",
            total_input: 0,
            rows: [],
        }),
    },
});

const formatNumber = (value) => new Intl.NumberFormat("id-ID").format(Number(value ?? 0));

const formatPercent = (value) =>
    `${Number(value ?? 0).toLocaleString("id-ID", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })}%`;

const rankClass = (rank) => {
    if (rank === 1) return "bg-amber-100 text-amber-700";
    if (rank === 2) return "bg-slate-100 text-slate-700";
    if (rank === 3) return "bg-orange-100 text-orange-700";
    return "bg-slate-50 text-slate-500";
};
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                <i class="pi pi-map-marker text-amber-500 text-xs" />
                Top Area Activity
            </div>
            <span class="text-xs text-slate-400">{{ props.data?.period_label ?? "30 hari terakhir" }}</span>
        </div>

        <div class="border-b border-slate-100 bg-slate-50/60 px-4 py-2 text-[11px] text-slate-500">
            Total input periode ini: <span class="font-semibold text-slate-700">{{ formatNumber(props.data?.total_input) }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-white text-left text-xs font-semibold text-slate-500">
                        <th class="px-3 py-2">Rank</th>
                        <th class="px-3 py-2">Kecamatan</th>
                        <th class="px-3 py-2 text-right">Total input</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <tr v-for="(item, index) in props.data?.rows ?? []" :key="`${item.district_id}-${index}`" class="hover:bg-slate-50/60">
                        <td class="px-3 py-2.5 align-top">
                            <span
                                class="inline-flex h-6 min-w-6 items-center justify-center rounded-full px-2 text-[11px] font-bold"
                                :class="rankClass(index + 1)"
                            >
                                {{ index + 1 }}
                            </span>
                        </td>
                        <td class="px-3 py-2.5">
                            <div class="space-y-1.5">
                                <p class="font-medium text-slate-700">{{ item.district_name || "Tanpa kecamatan" }}</p>
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                                    <div
                                        class="h-full rounded-full bg-amber-500"
                                        :style="{ width: `${Math.max(0, Math.min(Number(item.percentage ?? 0), 100))}%` }"
                                    />
                                </div>
                                <p class="text-[11px] text-slate-400">Share periode: {{ formatPercent(item.percentage) }}</p>
                            </div>
                        </td>
                        <td class="ui-tabular px-3 py-2.5 text-right font-semibold text-slate-800 align-top">
                            {{ formatNumber(item.total_input) }}
                        </td>
                    </tr>

                    <tr v-if="(props.data?.rows ?? []).length === 0">
                        <td colspan="3" class="px-4 py-10 text-center text-sm text-slate-400">
                            Belum ada aktivitas area pada periode ini.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
