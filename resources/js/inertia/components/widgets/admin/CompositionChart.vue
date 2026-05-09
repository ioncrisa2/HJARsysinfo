<script setup>
import { onMounted, ref, watch } from "vue";
import Chart from "chart.js/auto";

const props = defineProps({
    chartData: {
        type: Object,
        required: true
    }
});

const canvasRef = ref(null);
let chartInstance = null;

const initChart = () => {
    if (chartInstance) {
        chartInstance.destroy();
    }

    if (!canvasRef.value) return;

    const ctx = canvasRef.value.getContext("2d");

    chartInstance = new Chart(ctx, {
        type: "doughnut",
        data: {
            labels: props.chartData.labels,
            datasets: [
                {
                    ...props.chartData.datasets[0],
                    borderWidth: 0,
                    hoverOffset: 15,
                }
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: "70%",
            plugins: {
                legend: {
                    position: "bottom",
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: { size: 11, weight: "medium" },
                        color: "#475569",
                    },
                },
                tooltip: {
                    padding: 12,
                    backgroundColor: "#1e293b",
                    cornerRadius: 8,
                },
            },
        },
    });
};

onMounted(() => {
    initChart();
});

watch(() => props.chartData, () => {
    initChart();
}, { deep: true });
</script>

<template>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex flex-col">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="font-bold text-slate-800">Komposisi Listing</h3>
                <p class="text-xs text-slate-500">Berdasarkan jenis penawaran</p>
            </div>
            <div class="h-8 w-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600">
                <i class="pi pi-chart-pie" />
            </div>
        </div>
        
        <div class="flex-1 min-h-[300px] flex items-center justify-center">
            <canvas ref="canvasRef"></canvas>
        </div>
    </div>
</template>
