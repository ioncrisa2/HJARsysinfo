<script setup>
import { computed, ref, watch } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import Message from "primevue/message";
import AppLayout from "../Layouts/AppLayout.vue";
import MapWidget from "../components/widgets/MapWidget.vue";
import StatCards from "../components/widgets/StatCards.vue";
import RecentDataTable from "../components/widgets/RecentDataTable.vue";
import ListingRatioChart from "../components/widgets/ListingRatioChart.vue";
import TopContributorTable from "../components/widgets/TopContributorTable.vue";
import DataFreshnessWidget from "../components/widgets/DataFreshnessWidget.vue";
import TopAreaActivityTable from "../components/widgets/TopAreaActivityTable.vue";
import ObjectTypeCountTable from "../components/widgets/ObjectTypeCountTable.vue";
import { useResponsiveCanvasChart } from "../composables/useResponsiveCanvasChart";

defineOptions({ layout: AppLayout });

const page = usePage();
const canViewData = computed(() => Boolean(page.props.can?.viewData));
const canCreateData = computed(() => Boolean(page.props.can?.createData));
const deleteRequestAlert = computed(() => page.props.deleteRequestAlert ?? null);

const dashboardVariant = computed(() => page.props.dashboardVariant ?? "default");
const isDataContributorDashboard = computed(() => dashboardVariant.value === "data_contributor");
const canWidgets = computed(() => page.props.canWidgets ?? {});
const mapPoints = computed(() => page.props.mapPoints ?? []);
const recentData = computed(() => page.props.recentData ?? []);
const monthlyData = computed(() => page.props.monthlyData ?? []);
const listingRatioMonthly = computed(() => page.props.listingRatioMonthly ?? { labels: [], series: [], month_totals: [] });
const topContributors = computed(() => page.props.topContributors ?? []);
const dataFreshness = computed(() => page.props.dataFreshness ?? { basis: "Berdasarkan tanggal_data", total: 0, with_date: 0, missing_date: 0, buckets: [] });
const topAreaActivity = computed(() => page.props.topAreaActivity ?? { period_label: "30 hari terakhir", total_input: 0, rows: [] });
const objectTypeCounts = computed(() => page.props.objectTypeCounts ?? { total_records: 0, rows: [] });
const stats = computed(() => page.props.stats ?? { total: 0, this_month: 0, last_month: 0, province_count: 0 });
const listingOptions = computed(() => [{ label: "Semua Listing", value: null }, ...(page.props.jenisListingOptions ?? [])]);
const hasAnyVisibleWidget = computed(() => Object.values(canWidgets.value).some(Boolean));

// ── Chart ──────────────────────────────────────────────────────────────────
const chartContainer = ref(null);

const drawMonthlyChart = ({ ctx, width: W, height: H }) => {
    ctx.clearRect(0, 0, W, H);

    if (monthlyData.value.length === 0) return;

    const data = monthlyData.value;
    const labels = data.map((d) => d.month);
    const values = data.map((d) => d.count);
    const maxVal = Math.max(...values, 1);

    const padLeft = 40, padRight = 20, padTop = 24, padBottom = 44;
    const chartW = W - padLeft - padRight;
    const chartH = H - padTop - padBottom;
    const xStep = labels.length > 1 ? chartW / (labels.length - 1) : 0;
    const toX = (i) => padLeft + i * xStep;
    const toY = (v) => padTop + chartH - (v / maxVal) * chartH;

    // Grid lines
    for (let i = 0; i <= 4; i++) {
        const y = padTop + (chartH / 4) * i;
        ctx.strokeStyle = "#f1f5f9"; ctx.lineWidth = 1;
        ctx.beginPath(); ctx.moveTo(padLeft, y); ctx.lineTo(W - padRight, y); ctx.stroke();
        ctx.fillStyle = "#cbd5e1";
        ctx.font = "10px ui-sans-serif,system-ui";
        ctx.textAlign = "right";
        ctx.fillText(Math.round(maxVal - (maxVal / 4) * i), padLeft - 6, y + 3);
    }

    const drawCurve = () => {
        ctx.beginPath();
        ctx.moveTo(toX(0), toY(values[0]));
        for (let i = 1; i < values.length; i++) {
            const cpX = (toX(i - 1) + toX(i)) / 2;
            ctx.bezierCurveTo(cpX, toY(values[i - 1]), cpX, toY(values[i]), toX(i), toY(values[i]));
        }
    };

    drawCurve();
    ctx.lineTo(toX(values.length - 1), padTop + chartH);
    ctx.lineTo(toX(0), padTop + chartH);
    ctx.closePath();
    ctx.fillStyle = "rgba(245,158,11,0.08)"; ctx.fill();

    drawCurve();
    ctx.strokeStyle = "#f59e0b"; ctx.lineWidth = 2.5; ctx.lineJoin = "round"; ctx.stroke();

    values.forEach((v, i) => {
        const x = toX(i), y = toY(v);
        ctx.beginPath(); ctx.arc(x, y, 4, 0, Math.PI * 2);
        ctx.fillStyle = "#fff"; ctx.fill();
        ctx.strokeStyle = "#f59e0b"; ctx.lineWidth = 2; ctx.stroke();
        if (v > 0) {
            ctx.fillStyle = "#0f172a";
            ctx.font = "bold 10px ui-sans-serif,system-ui";
            ctx.textAlign = "center";
            ctx.fillText(v, x, y - 10);
        }
    });

    ctx.fillStyle = "#94a3b8";
    ctx.font = "10px ui-sans-serif,system-ui";
    ctx.textAlign = "center";
    labels.forEach((label, i) => {
        if (labels.length > 8 && i % 2 !== 0) return;
        ctx.fillText(label, toX(i), H - padBottom + 16);
    });
};

const { renderNextTick } = useResponsiveCanvasChart(chartContainer, drawMonthlyChart);

watch(monthlyData, () => renderNextTick(), { deep: true });
</script>

<template>

    <Head title="Dashboard" />

    <div class="space-y-4 py-3">

        <Message v-if="page.props.flash?.success" severity="success" class="mb-2">
            {{ page.props.flash.success }}
        </Message>

        <Link
            v-if="deleteRequestAlert"
            :href="deleteRequestAlert.href"
            class="flex min-h-11 items-center justify-between gap-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-600"
        >
            <span class="flex items-center gap-2 font-semibold">
                <i class="pi pi-exclamation-triangle" aria-hidden="true" />
                {{ deleteRequestAlert.message }}
            </span>
            <span class="shrink-0 font-bold">Tinjau <i class="pi pi-arrow-right ml-1" aria-hidden="true" /></span>
        </Link>

        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-balance text-xl font-semibold text-slate-900">Dashboard</h1>
                <p class="text-pretty text-sm text-slate-500">Ringkasan data pembanding Anda</p>
            </div>
            <div class="flex items-center gap-2">
                <Link v-if="canViewData" href="/app/pembanding"
                    class="rounded-[var(--radius-sm)] border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 hover:border-slate-300">
                    <i class="pi pi-database mr-1.5 text-xs" />
                    Bank Data
                </Link>
                <Link v-if="canCreateData" href="/app/pembanding/create"
                    class="rounded-[var(--radius-sm)] bg-amber-500 px-3 py-2 text-sm font-semibold text-white transition hover:bg-amber-600">
                    <i class="pi pi-plus mr-1.5 text-xs" />
                    Tambah Data
                </Link>
            </div>
        </div>

        <!-- Map Widget -->
        <MapWidget v-if="canWidgets.map" :points="mapPoints" :listing-options="listingOptions" height="460px" />

        <!-- Stat Cards -->
        <StatCards v-if="canWidgets.statsOverview" :stats="stats" />

        <template v-if="!isDataContributorDashboard">
            <!-- Monthly Chart -->
            <div v-if="canWidgets.dataEntryTrendChart" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 px-3 py-2.5">
                    <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                        <i class="pi pi-chart-line text-amber-500 text-xs" />
                        Input Data Per Bulan
                    </div>
                    <span class="text-xs text-slate-400">12 bulan terakhir</span>
                </div>
                <div class="p-3">
                    <canvas ref="chartContainer" class="h-44 w-full" style="display:block" />
                </div>
            </div>

            <div
                v-if="canWidgets.listingCompositionChart || canWidgets.topContributorTable"
                class="grid gap-3 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]"
            >
                <ListingRatioChart v-if="canWidgets.listingCompositionChart" :data="listingRatioMonthly" />
                <TopContributorTable v-if="canWidgets.topContributorTable" :data="topContributors" />
            </div>

            <div
                v-if="canWidgets.dataFreshnessWidget || canWidgets.topAreaActivityTable"
                class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.35fr)]"
            >
                <DataFreshnessWidget v-if="canWidgets.dataFreshnessWidget" :data="dataFreshness" />
                <TopAreaActivityTable v-if="canWidgets.topAreaActivityTable" :data="topAreaActivity" />
            </div>

            <ObjectTypeCountTable v-if="canWidgets.objectTypeCountTable" :data="objectTypeCounts" />

            <!-- Recent Data -->
            <RecentDataTable v-if="canWidgets.latestPembandingTable" :data="recentData" />
        </template>

        <div
            v-if="!hasAnyVisibleWidget"
            class="rounded-2xl border border-slate-200 bg-white p-6 text-sm font-medium text-slate-500 shadow-sm"
        >
            Tidak ada widget dashboard yang diizinkan untuk role Anda.
        </div>

    </div>
</template>

