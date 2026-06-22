<script setup>
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";
import AdminLayout from "../../Layouts/AdminLayout.vue";
import AdminStatCards from "../../components/widgets/admin/AdminStatCards.vue";
import TrendChart from "../../components/widgets/admin/TrendChart.vue";
import CompositionChart from "../../components/widgets/admin/CompositionChart.vue";
import AdminRecentDataTable from "../../components/widgets/admin/AdminRecentDataTable.vue";
import AdminMapWidget from "../../components/widgets/admin/AdminMapWidget.vue";

const props = defineProps({
    stats: Object,
    trendChart: Object,
    compositionChart: Object,
    latestPembanding: Array,
    markers: Array,
    deleteRequestAlert: {
        type: Object,
        default: null,
    },
});

const isDeleteRequestAlertDismissed = ref(false);

const shouldShowDeleteRequestAlert = computed(() => {
    return Boolean(props.deleteRequestAlert?.count) && !isDeleteRequestAlertDismissed.value;
});

const dismissDeleteRequestAlert = () => {
    isDeleteRequestAlertDismissed.value = true;
};

const openModerationDesk = () => {
    dismissDeleteRequestAlert();
    router.visit(props.deleteRequestAlert?.href ?? "/admin/moderation");
};
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

            <section
                v-if="shouldShowDeleteRequestAlert"
                role="alert"
                aria-live="polite"
                class="relative overflow-hidden rounded-2xl border border-amber-200 bg-amber-50 shadow-sm"
            >
                <button
                    type="button"
                    class="flex w-full items-start gap-4 p-5 pr-14 text-left transition hover:bg-amber-100/60 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-slate-50"
                    @click="openModerationDesk"
                >
                    <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                        <i class="pi pi-exclamation-triangle text-lg" />
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-black uppercase tracking-widest text-amber-700">
                            Moderation Alert
                        </span>
                        <span class="mt-1 block text-base font-bold text-slate-900">
                            {{ deleteRequestAlert.title }}
                        </span>
                        <span class="mt-1 block text-sm leading-6 text-slate-600">
                            {{ deleteRequestAlert.message }} Klik untuk membuka Moderation Desk.
                        </span>
                    </span>
                </button>
                <button
                    type="button"
                    class="absolute right-4 top-4 flex h-9 w-9 items-center justify-center rounded-xl text-amber-700 transition hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-500"
                    aria-label="Tutup pemberitahuan request hapus data"
                    @click.stop="dismissDeleteRequestAlert"
                >
                    <i class="pi pi-times" />
                </button>
            </section>

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
