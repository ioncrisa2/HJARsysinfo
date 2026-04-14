<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    record: {
        type: Object,
        default: () => ({}),
    },
});
</script>

<template>
    <nav class="flex items-center gap-1.5 text-xs text-slate-500" aria-label="Breadcrumb">
        <Link href="/home/pembanding" class="inline-flex items-center gap-2 font-semibold text-slate-700 hover:text-slate-900">
            <i class="pi pi-database text-[11px] text-amber-600" aria-hidden="true" />
            Bank Data
        </Link>
        <i class="pi pi-chevron-right text-[9px] text-slate-300" aria-hidden="true" />
        <Link :href="`/home/pembanding/${record.id}`" class="font-semibold text-slate-700 hover:text-slate-900">
            Detail #{{ record.id }}
        </Link>
        <i class="pi pi-chevron-right text-[9px] text-slate-300" aria-hidden="true" />
        <span class="font-medium text-slate-600">Riwayat</span>
    </nav>
</template>

