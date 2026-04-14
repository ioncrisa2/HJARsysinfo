<script setup>
import { Link } from "@inertiajs/vue3";
import UiSurface from "../../ui/UiSurface.vue";

defineProps({
    record: {
        type: Object,
        default: () => ({}),
    },
    createdAtLabel: {
        type: String,
        default: "n/a",
    },
    canRequestDelete: {
        type: Boolean,
        default: true,
    },
    hasPendingDeleteRequest: {
        type: Boolean,
        default: false,
    },
});

defineEmits(["request-delete"]);
</script>

<template>
    <UiSurface padding="none" class="overflow-hidden">
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 bg-slate-50/70 px-4 py-3">
            <div class="mr-auto flex flex-wrap items-center gap-2">
                <span
                    v-if="record.jenis_listing"
                    class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-700"
                >
                    <span class="size-1.5 rounded-full bg-amber-500" aria-hidden="true" />
                    {{ record.jenis_listing }}
                </span>

                <span
                    v-if="record.jenis_objek"
                    class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-700"
                >
                    <span class="size-1.5 rounded-full bg-slate-400" aria-hidden="true" />
                    {{ record.jenis_objek }}
                </span>

                <span
                    v-if="record.latitude && record.longitude"
                    class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-700"
                >
                    <i class="pi pi-map-marker text-[11px] text-amber-600" aria-hidden="true" />
                    GPS tersedia
                </span>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <Link
                    :href="`/home/pembanding/${record.id}/history`"
                    class="inline-flex h-9 items-center justify-center gap-2 rounded-[var(--radius-sm)] border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                >
                    <i class="pi pi-history text-[12px]" aria-hidden="true" />
                    <span class="hidden sm:inline">Riwayat</span>
                </Link>

                <button
                    type="button"
                    :disabled="!canRequestDelete"
                    class="inline-flex h-9 items-center justify-center gap-2 rounded-[var(--radius-sm)] border px-3 text-xs font-semibold transition
                        disabled:cursor-not-allowed disabled:border-slate-200 disabled:bg-slate-100 disabled:text-slate-400"
                    :class="canRequestDelete ? 'border-red-200 bg-white text-red-700 hover:bg-red-50' : ''"
                    @click="$emit('request-delete')"
                >
                    <i class="pi pi-trash text-[12px]" aria-hidden="true" />
                    <span class="hidden sm:inline">
                        {{ hasPendingDeleteRequest ? "Menunggu Approval" : "Request Hapus" }}
                    </span>
                    <span class="sm:hidden">Hapus</span>
                </button>

                <Link
                    :href="`/home/pembanding/${record.id}/edit`"
                    class="inline-flex h-9 items-center justify-center gap-2 rounded-[var(--radius-sm)] bg-amber-500 px-3 text-xs font-semibold text-white transition hover:bg-amber-600"
                >
                    <i class="pi pi-pencil text-[12px]" aria-hidden="true" />
                    Edit
                </Link>
            </div>
        </div>

        <div class="px-4 py-4">
            <h1 class="text-balance text-lg font-semibold leading-snug text-slate-900 sm:text-2xl">
                {{ record.alamat ?? "Tanpa Alamat" }}
            </h1>
            <p v-if="record.location" class="mt-1 flex items-center gap-1.5 text-pretty text-xs text-slate-500">
                <i class="pi pi-map-marker text-[11px]" aria-hidden="true" />
                {{ record.location }}
            </p>

            <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-1.5 border-t border-slate-100 pt-4 text-xs text-slate-500">
                <span class="flex items-center gap-1.5">
                    <i class="pi pi-user text-[11px]" aria-hidden="true" />
                    Dibuat oleh <span class="font-semibold text-slate-700">{{ record.created_by ?? "n/a" }}</span>
                </span>
                <span class="flex items-center gap-1.5">
                    <i class="pi pi-calendar text-[11px]" aria-hidden="true" />
                    {{ createdAtLabel }}
                </span>
                <span v-if="record.updated_by" class="flex items-center gap-1.5">
                    <i class="pi pi-refresh text-[11px]" aria-hidden="true" />
                    Diperbarui oleh <span class="font-semibold text-slate-700">{{ record.updated_by }}</span>
                </span>
            </div>
        </div>
    </UiSurface>
</template>

