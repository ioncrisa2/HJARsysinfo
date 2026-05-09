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
    
    // Create gradient
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, "rgba(59, 130, 246, 0.2)");
    gradient.addColorStop(1, "rgba(59, 130, 246, 0.0)");

    chartInstance = new Chart(ctx, {
        type: "line",
        data: {
            labels: props.chartData.labels,
            datasets: [
                {
                    ...props.chartData.datasets[0],
                    borderColor: "#3b82f6",
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: "#fff",
                    pointBorderColor: "#3b82f6",
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                }
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    mode: "index",
                    intersect: false,
                    padding: 12,
                    backgroundColor: "#1e293b",
                    titleFont: { size: 13, weight: "bold" },
                    bodyFont: { size: 12 },
                    cornerRadius: 8,
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: "#f1f5f9",
                    },
                    ticks: {
                        font: { size: 11 },
                        color: "#64748b",
                    },
                },
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        font: { size: 11 },
                        color: "#64748b",
                    },
                },
            },
            interaction: {
                mode: "nearest",
                axis: "x",
                intersect: false,
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
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="font-bold text-slate-800">Tren Input Data</h3>
                <p class="text-xs text-slate-500">Jumlah data masuk per bulan (Tahun Ini)</p>
            </div>
            <div class="h-8 w-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600">
                <i class="pi pi-chart-line" />
            </div>
        </div>
        
        <div class="h-[300px] w-full">
            <canvas ref="canvasRef"></canvas>
        </div>
    </div>
</template>
