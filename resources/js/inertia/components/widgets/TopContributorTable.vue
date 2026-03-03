<script setup>
const props = defineProps({
    data: {
        type: Array,
        default: () => [],
    },
});

const formatNumber = (value) => new Intl.NumberFormat("id-ID").format(Number(value ?? 0));

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
                <i class="pi pi-users text-amber-500 text-xs" />
                Top Contributor
            </div>
            <span class="text-xs text-slate-400">Berdasarkan total input data</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/60 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                        <th class="px-4 py-2.5">Rank</th>
                        <th class="px-4 py-2.5">Nama</th>
                        <th class="px-4 py-2.5 text-right">Total Input</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <tr v-for="(item, index) in props.data" :key="`${item.name}-${index}`" class="hover:bg-slate-50/60">
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex h-6 min-w-6 items-center justify-center rounded-full px-2 text-[11px] font-bold"
                                :class="rankClass(index + 1)"
                            >
                                {{ index + 1 }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-medium text-slate-700">
                            {{ item.name || "Tidak diketahui" }}
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-800">
                            {{ formatNumber(item.total_input) }}
                        </td>
                    </tr>

                    <tr v-if="props.data.length === 0">
                        <td colspan="3" class="px-4 py-10 text-center text-sm text-slate-400">
                            Belum ada data contributor.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
