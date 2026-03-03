<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    record: {
        type: Object,
        default: () => ({}),
    },
    createdAtLabel: {
        type: String,
        default: "n/a",
    },
});
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <!-- Top action bar -->
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 bg-slate-50/70 px-5 py-3">
            <!-- Type badges -->
            <div class="flex items-center gap-2 mr-auto flex-wrap">
                <span
                    v-if="record.jenis_listing"
                    class="rounded-full bg-emerald-500 px-3 py-0.5 text-[11px] font-bold uppercase text-white shadow-sm"
                >
                    {{ record.jenis_listing }}
                </span>
                <span
                    v-if="record.jenis_objek"
                    class="rounded-full bg-amber-500 px-3 py-0.5 text-[11px] font-bold uppercase text-white shadow-sm"
                >
                    {{ record.jenis_objek }}
                </span>
                <span
                    v-if="record.latitude && record.longitude"
                    class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-600"
                >
                    <i class="pi pi-map-marker text-[9px]" /> GPS Tersedia
                </span>
            </div>

            <!-- Actions -->
            <div class="flex shrink-0 items-center gap-2">
                <Link
                    :href="`/home/pembanding/${record.id}/history`"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
                >
                    <i class="pi pi-history text-[11px]" />
                    <span class="hidden sm:inline">Riwayat</span>
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

        <!-- Main content -->
        <div class="px-5 py-5">
            <h1 class="text-lg font-black leading-snug text-slate-900 sm:text-2xl">
                {{ record.alamat ?? "Tanpa Alamat" }}
            </h1>
            <p v-if="record.location" class="mt-1 flex items-center gap-1 text-xs text-slate-400">
                <i class="pi pi-map-marker text-[10px]" />
                {{ record.location }}
            </p>

            <!-- Metadata row -->
            <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-1.5 border-t border-slate-100 pt-4 text-xs text-slate-400">
                <span class="flex items-center gap-1.5">
                    <i class="pi pi-user text-[10px]" />
                    Dibuat oleh
                    <strong class="font-semibold text-slate-600">{{ record.created_by ?? "n/a" }}</strong>
                </span>
                <span class="flex items-center gap-1.5">
                    <i class="pi pi-calendar text-[10px]" />
                    {{ createdAtLabel }}
                </span>
                <span v-if="record.updated_by" class="flex items-center gap-1.5">
                    <i class="pi pi-refresh text-[10px]" />
                    Diperbarui oleh
                    <strong class="font-semibold text-slate-600">{{ record.updated_by }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>
