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
    <nav class="flex items-center gap-1.5 text-xs text-slate-400">
        <Link href="/home/pembanding" class="inline-flex items-center gap-1 font-medium hover:text-amber-600 transition-colors">
            <i class="pi pi-database text-[10px]" />
            Bank Data
        </Link>
        <i class="pi pi-chevron-right text-[9px] text-slate-300" />
        <Link :href="`/home/pembanding/${record.id}`" class="font-medium hover:text-amber-600 transition-colors">
            Detail #{{ record.id }}
        </Link>
        <i class="pi pi-chevron-right text-[9px] text-slate-300" />
        <span class="font-semibold text-slate-600">Riwayat</span>
    </nav>
</template>
