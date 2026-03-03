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
        <div class="flex flex-wrap items-center gap-3 px-5 pt-5 pb-4">
            <div class="mr-auto">
                <h1 class="text-xl font-bold text-slate-900 tracking-tight">Bank Data Pembanding</h1>
                <p class="mt-0.5 text-sm text-slate-500">
                    Gunakan filter untuk menyaring data berdasarkan lokasi, jenis, atau tanggal.
                </p>
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
                    class="inline-flex items-center gap-1.5 rounded-lg bg-amber-500 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-600 transition-colors"
                >
                    <i class="pi pi-plus text-xs" />
                    Tambah Data
                </Link>
            </div>
        </div>

        <!-- Stats bar -->
        <div class="grid grid-cols-2 sm:grid-cols-4 divide-x divide-slate-100 border-t border-slate-100 bg-slate-50/70">
            <div class="flex flex-col items-center gap-0.5 px-5 py-3">
                <span class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">Total Data</span>
                <span class="text-2xl font-black text-slate-800 tabular-nums">
                    {{ (total ?? 0).toLocaleString("id-ID") }}
                </span>
            </div>
            <div class="flex flex-col items-center gap-0.5 px-5 py-3">
                <span class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">Ditampilkan</span>
                <span class="text-2xl font-black text-amber-600 tabular-nums">
                    {{ (records?.data ?? []).length }}
                </span>
            </div>
            <div class="flex flex-col items-center gap-0.5 px-5 py-3">
                <span class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">Halaman</span>
                <span class="text-2xl font-black text-slate-800 tabular-nums">
                    {{ records?.current_page ?? 1 }} / {{ records?.last_page ?? 1 }}
                </span>
            </div>
            <div class="flex flex-col items-center gap-0.5 px-5 py-3">
                <span class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">Filter Aktif</span>
                <span
                    class="text-2xl font-black tabular-nums"
                    :class="activeFilterCount > 0 ? 'text-emerald-600' : 'text-slate-300'"
                >
                    {{ activeFilterCount }}
                </span>
            </div>
        </div>
    </div>
</template>
