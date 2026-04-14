<script setup>
import { Link } from "@inertiajs/vue3";
import UiSurface from "../../ui/UiSurface.vue";

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
    <UiSurface padding="none" class="overflow-hidden">
        <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 bg-slate-50/70 px-4 py-3">
            <div class="mr-auto flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-2 text-sm font-semibold text-slate-900">
                    <i class="pi pi-history text-[12px] text-amber-600" aria-hidden="true" />
                    Riwayat Perubahan
                </span>
                <span class="ui-tabular rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600">
                    {{ total }} aktivitas
                </span>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <Link
                    :href="`/home/pembanding/${record.id}`"
                    class="inline-flex h-9 items-center justify-center gap-2 rounded-[var(--radius-sm)] border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                >
                    <i class="pi pi-arrow-left text-[12px]" aria-hidden="true" />
                    <span class="hidden sm:inline">Kembali</span>
                    <span class="sm:hidden">Detail</span>
                </Link>
                <Link
                    :href="`/home/pembanding/${record.id}/edit`"
                    class="inline-flex h-9 items-center justify-center gap-2 rounded-[var(--radius-sm)] bg-amber-500 px-3 text-xs font-semibold text-white hover:bg-amber-600"
                >
                    <i class="pi pi-pencil text-[12px]" aria-hidden="true" />
                    Edit
                </Link>
            </div>
        </div>

        <div class="px-4 py-4">
            <h1 class="text-balance text-lg font-semibold leading-snug text-slate-900 sm:text-xl">
                {{ record.alamat ?? "Tanpa Alamat" }}
            </h1>
            <p v-if="record.location" class="mt-1 flex items-center gap-1.5 text-pretty text-xs text-slate-500">
                <i class="pi pi-map-marker text-[11px]" aria-hidden="true" />
                {{ record.location }}
            </p>
            <p class="mt-3 text-pretty text-xs text-slate-500">
                Menampilkan hingga <span class="font-semibold text-slate-700">100 aktivitas</span> terbaru.
            </p>
        </div>
    </UiSurface>
</template>

