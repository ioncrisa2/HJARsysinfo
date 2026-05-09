<script setup>
import AdminLayout from "../../Layouts/AdminLayout.vue";
import AdminStatCards from "../../components/widgets/admin/AdminStatCards.vue";
import TrendChart from "../../components/widgets/admin/TrendChart.vue";
import CompositionChart from "../../components/widgets/admin/CompositionChart.vue";
import AdminRecentDataTable from "../../components/widgets/admin/AdminRecentDataTable.vue";
import AdminMapWidget from "../../components/widgets/admin/AdminMapWidget.vue";

defineProps({
    stats: Object,
    trendChart: Object,
    compositionChart: Object,
    latestPembanding: Array,
    markers: Array,
});
</script>

<template>
    <AdminLayout title="Dashboard — Admin">
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Admin Overview</h2>
                    <p class="text-slate-500 text-sm mt-1">Sistem informasi pembanding data properti.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-xs font-semibold text-slate-600 uppercase tracking-widest">System Live</span>
                </div>
            </div>

            <!-- Stats Cards -->
            <AdminStatCards :stats="stats" />

            <!-- Middle Section: Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    <TrendChart :chart-data="trendChart" />
                </div>
                <div>
                    <CompositionChart :chart-data="compositionChart" />
                </div>
            </div>

            <!-- Bottom Section: Table and Map -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <AdminRecentDataTable :data="latestPembanding" />
                <AdminMapWidget :markers="markers" />
            </div>
        </div>
    </AdminLayout>
</template>
