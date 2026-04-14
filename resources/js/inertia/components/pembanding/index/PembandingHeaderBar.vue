<script setup>
import { Link } from "@inertiajs/vue3";
import Button from "primevue/button";
import SplitButton from "primevue/splitbutton";

const props = defineProps({
    total: { type: Number, default: 0 },
    activeFilterCount: { type: Number, default: 0 },
    records: {
        type: Object,
        default: () => ({ data: [], total: 0, current_page: 1, last_page: 1 }),
    },
});

const emit = defineEmits(["exportExcel", "exportPdf", "openFilterDrawer"]);

const exportItems = [
    {
        label: "Export PDF",
        icon: "pi pi-file-pdf",
        command: () => emit("exportPdf"),
    },
];
</script>

<template>
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <!-- Top bar -->
        <div class="flex flex-wrap items-center gap-3 px-4 py-3">
            <div class="mr-auto">
                <h1 class="text-balance text-lg font-semibold text-slate-900">Bank Data Pembanding</h1>
                <p class="text-pretty mt-0.5 text-sm text-slate-500">Cari dan ekspor data sesuai filter.</p>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <!-- Filter button — drawer -->
                <Button
                    icon="pi pi-filter"
                    label="Filter"
                    size="small"
                    severity="secondary"
                    outlined
                    class="!h-9"
                    :badge="activeFilterCount > 0 ? String(activeFilterCount) : undefined"
                    badge-severity="warn"
                    @click="emit('openFilterDrawer')"
                />

                <!-- Export SplitButton -->
                <SplitButton
                    label="Export Excel"
                    icon="pi pi-download"
                    size="small"
                    class="!h-9"
                    :model="exportItems"
                    @click="emit('exportExcel')"
                />

                <Link
                    href="/home/pembanding/create"
                    class="inline-flex items-center gap-1.5 rounded-[var(--radius-sm)] bg-amber-500 px-3 py-2 text-sm font-semibold text-white hover:bg-amber-600 transition-colors"
                >
                    <i class="pi pi-plus text-xs" />
                    Tambah Data
                </Link>
            </div>
        </div>

        <!-- Stats bar -->
        <div class="grid grid-cols-2 sm:grid-cols-4 divide-x divide-slate-100 border-t border-slate-100 bg-slate-50/70">
            <div class="flex flex-col items-center gap-0.5 px-5 py-3">
                <span class="text-[11px] font-semibold text-slate-500">Total</span>
                <span class="ui-tabular text-2xl font-semibold text-slate-900">
                    {{ (total ?? 0).toLocaleString("id-ID") }}
                </span>
            </div>
            <div class="flex flex-col items-center gap-0.5 px-5 py-3">
                <span class="text-[11px] font-semibold text-slate-500">Ditampilkan</span>
                <span class="ui-tabular text-2xl font-semibold text-slate-900">
                    {{ (records?.data ?? []).length }}
                </span>
            </div>
            <div class="flex flex-col items-center gap-0.5 px-5 py-3">
                <span class="text-[11px] font-semibold text-slate-500">Halaman</span>
                <span class="ui-tabular text-2xl font-semibold text-slate-900">
                    {{ records?.current_page ?? 1 }} / {{ records?.last_page ?? 1 }}
                </span>
            </div>
            <div class="flex flex-col items-center gap-0.5 px-5 py-3">
                <span class="text-[11px] font-semibold text-slate-500">Filter aktif</span>
                <span
                    class="ui-tabular text-2xl font-semibold"
                    :class="activeFilterCount > 0 ? 'text-slate-900' : 'text-slate-300'"
                >
                    {{ activeFilterCount }}
                </span>
            </div>
        </div>
    </div>
</template>
