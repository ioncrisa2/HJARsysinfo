import { onBeforeUnmount, onMounted, ref, unref, watch } from "vue";
import Chart from "chart.js/auto";

/**
 * Manage a Chart.js instance for a canvas ref with automatic rerender and cleanup.
 *
 * @param {import("vue").WatchSource|Function|Object} chartData
 * @param {Function} buildConfig receives { canvas, ctx, chartData } and returns Chart.js config
 * @param {{ watch?: import("vue").WatchOptions }} options
 * @returns {{ canvasRef: import("vue").Ref, renderChart: Function, destroyChart: Function }}
 */
export function useChartJs(chartData, buildConfig, options = {}) {
    const canvasRef = ref(null);
    let chartInstance = null;

    const resolveChartData = () => (typeof chartData === "function" ? chartData() : unref(chartData));

    const destroyChart = () => {
        if (!chartInstance) return;

        chartInstance.destroy();
        chartInstance = null;
    };

    const renderChart = () => {
        destroyChart();

        if (!canvasRef.value) return;

        const ctx = canvasRef.value.getContext("2d");
        if (!ctx) return;

        chartInstance = new Chart(ctx, buildConfig({
            canvas: canvasRef.value,
            ctx,
            chartData: resolveChartData(),
        }));
    };

    onMounted(renderChart);

    watch(chartData, renderChart, {
        deep: true,
        ...(options.watch ?? {}),
    });

    onBeforeUnmount(destroyChart);

    return {
        canvasRef,
        renderChart,
        destroyChart,
    };
}
