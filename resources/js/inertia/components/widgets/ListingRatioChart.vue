<script setup>
import { computed, ref, watch } from "vue";
import { useResponsiveCanvasChart } from "../../composables/useResponsiveCanvasChart";

const props = defineProps({
    data: {
        type: Object,
        default: () => ({ labels: [], series: [], month_totals: [] }),
    },
});

const canvasRef = ref(null);

const palette = ["#f59e0b", "#10b981", "#0ea5e9", "#ef4444", "#8b5cf6", "#14b8a6"];

const labels = computed(() => props.data?.labels ?? []);
const monthTotals = computed(() => props.data?.month_totals ?? []);
const selectedMonthIndex = ref(-1);

const series = computed(() =>
    (props.data?.series ?? []).map((item, index) => ({
        ...item,
        color: item.color ?? palette[index % palette.length],
        ratios: Array.isArray(item.ratios) ? item.ratios.map((value) => Number(value ?? 0)) : [],
        counts: Array.isArray(item.counts) ? item.counts.map((value) => Number(value ?? 0)) : [],
    })),
);

const hasData = computed(() => labels.value.length > 0 && series.value.length > 0);

const monthOptions = computed(() =>
    labels.value
        .map((label, index) => ({ label, value: index }))
        .reverse(),
);

const selectedMonthLabel = computed(() => {
    const index = selectedMonthIndex.value;
    if (index < 0 || index >= labels.value.length) return "-";
    return labels.value[index];
});

const selectedMonthTotal = computed(() => {
    const index = selectedMonthIndex.value;
    if (index < 0 || index >= monthTotals.value.length) return 0;
    return Number(monthTotals.value[index] ?? 0);
});

const selectedMonthBreakdown = computed(() => {
    const index = selectedMonthIndex.value;
    if (index < 0) return [];

    return series.value
        .map((item) => ({
            id: item.id,
            name: item.name,
            color: item.color,
            count: Number(item.counts[index] ?? 0),
            ratio: Number(item.ratios[index] ?? 0),
        }))
        .sort((a, b) => b.count - a.count);
});

const formatNumber = (value) => new Intl.NumberFormat("id-ID").format(Number(value ?? 0));

const formatRatio = (value) =>
    `${Number(value ?? 0).toLocaleString("id-ID", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })}%`;

const renderChart = ({ ctx, width, height }) => {
    ctx.clearRect(0, 0, width, height);

    if (!hasData.value) return;

    const padLeft = 42;
    const padRight = 14;
    const padTop = 18;
    const padBottom = 42;
    const chartWidth = width - padLeft - padRight;
    const chartHeight = height - padTop - padBottom;
    const xStep = labels.value.length > 1 ? chartWidth / (labels.value.length - 1) : 0;

    const toX = (index) => padLeft + index * xStep;
    const toY = (value) => padTop + chartHeight - (Math.min(Math.max(value, 0), 100) / 100) * chartHeight;

    for (let i = 0; i <= 4; i += 1) {
        const ratio = i * 25;
        const y = toY(ratio);

        ctx.strokeStyle = "#f1f5f9";
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(padLeft, y);
        ctx.lineTo(width - padRight, y);
        ctx.stroke();

        ctx.fillStyle = "#94a3b8";
        ctx.font = "10px ui-sans-serif, system-ui";
        ctx.textAlign = "right";
        ctx.fillText(`${ratio}%`, padLeft - 6, y + 3);
    }

    series.value.forEach((item) => {
        if (item.ratios.length === 0) return;

        ctx.strokeStyle = item.color;
        ctx.lineWidth = 2;
        ctx.lineJoin = "round";
        ctx.lineCap = "round";

        ctx.beginPath();
        item.ratios.forEach((ratio, index) => {
            const x = toX(index);
            const y = toY(ratio);

            if (index === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        });
        ctx.stroke();

        item.ratios.forEach((ratio, index) => {
            const x = toX(index);
            const y = toY(ratio);

            ctx.beginPath();
            ctx.arc(x, y, 3, 0, Math.PI * 2);
            ctx.fillStyle = "#ffffff";
            ctx.fill();
            ctx.strokeStyle = item.color;
            ctx.lineWidth = 1.5;
            ctx.stroke();
        });
    });

    ctx.fillStyle = "#94a3b8";
    ctx.font = "10px ui-sans-serif, system-ui";
    ctx.textAlign = "center";
    labels.value.forEach((label, index) => {
        if (labels.value.length > 8 && index % 2 !== 0) return;
        ctx.fillText(label, toX(index), height - 16);
    });
};

const { renderNextTick } = useResponsiveCanvasChart(canvasRef, renderChart);

watch(labels, (newLabels) => {
    if (!newLabels.length) {
        selectedMonthIndex.value = -1;
        return;
    }

    if (selectedMonthIndex.value < 0 || selectedMonthIndex.value >= newLabels.length) {
        selectedMonthIndex.value = newLabels.length - 1;
    }
}, { immediate: true });

watch([labels, series], () => renderNextTick(), { deep: true });
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                <i class="pi pi-chart-line text-amber-500 text-xs" />
                Rasio Jenis Listing Per Bulan
            </div>
            <span class="text-xs text-slate-400">Dalam persen (%)</span>
        </div>

        <div v-if="hasData" class="space-y-3 p-4">
            <div class="flex flex-wrap gap-2">
                <span
                    v-for="item in series"
                    :key="item.id"
                    class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-medium text-slate-600"
                >
                    <span class="h-2 w-2 rounded-full" :style="{ backgroundColor: item.color }" />
                    {{ item.name }}
                </span>
            </div>

            <canvas ref="canvasRef" class="h-56 w-full" style="display:block" />

            <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                <div class="flex flex-wrap items-center gap-2">
                    <label class="text-xs font-semibold text-slate-500" for="listing-month-select">Pilih Bulan</label>
                    <select
                        id="listing-month-select"
                        v-model.number="selectedMonthIndex"
                        class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 focus:border-amber-400 focus:outline-none"
                    >
                        <option v-for="option in monthOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                    <span class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-1 text-[11px] text-slate-500">
                        <i class="pi pi-database text-[10px]" />
                        {{ selectedMonthLabel }}: {{ formatNumber(selectedMonthTotal) }} data
                    </span>
                </div>

                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    <div
                        v-for="item in selectedMonthBreakdown"
                        :key="item.id"
                        class="rounded-lg border border-slate-200 bg-white px-3 py-2"
                    >
                        <div class="flex items-center justify-between gap-2 text-[11px]">
                            <span class="font-semibold text-slate-700">{{ item.name }}</span>
                            <span class="text-slate-500">{{ formatNumber(item.count) }} data ({{ formatRatio(item.ratio) }})</span>
                        </div>
                        <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                            <div
                                class="h-full rounded-full transition-all duration-300"
                                :style="{ width: `${Math.max(0, Math.min(item.ratio, 100))}%`, backgroundColor: item.color }"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="px-4 py-10 text-center text-sm text-slate-400">
            Belum ada data cukup untuk menampilkan rasio jenis listing.
        </div>
    </div>
</template>
