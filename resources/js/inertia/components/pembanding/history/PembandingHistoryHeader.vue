<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    record: {
        type: Object,
        default: () => ({}),
    },
    total: {
        type: Number,
        default: 0,
    },
});
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <!-- Top bar -->
        <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 bg-slate-50/70 px-5 py-3">
            <div class="flex items-center gap-2 mr-auto">
                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-100">
                    <i class="pi pi-history text-amber-600" style="font-size: 12px" />
                </div>
                <span class="text-sm font-bold text-slate-700">Riwayat Perubahan</span>
                <!-- Total badge -->
                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-500 tabular-nums">
                    {{ total }} aktivitas
                </span>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <Link
                    :href="`/home/pembanding/${record.id}`"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
                >
                    <i class="pi pi-arrow-left text-[11px]" />
                    <span class="hidden sm:inline">Kembali ke Detail</span>
                    <span class="sm:hidden">Detail</span>
                </Link>
                <Link
                    :href="`/home/pembanding/${record.id}/edit`"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-amber-600"
                >
                    <i class="pi pi-pencil text-[11px]" />
                    Edit Data
                </Link>
            </div>
        </div>

        <!-- Record info -->
        <div class="px-5 py-4">
            <p class="text-base font-black text-slate-900 leading-snug">
                {{ record.alamat ?? "Tanpa Alamat" }}
            </p>
            <p v-if="record.location" class="mt-0.5 flex items-center gap-1 text-xs text-slate-400">
                <i class="pi pi-map-marker text-[10px]" />
                {{ record.location }}
            </p>
            <p class="mt-2 text-xs text-slate-400">
                Menampilkan hingga <strong class="text-slate-600">100 aktivitas</strong> terbaru pada data ini.
            </p>
        </div>
    </div>
</template>
