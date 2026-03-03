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
    <div class="flex items-center gap-1.5 text-xs text-slate-400">
        <Link href="/home/pembanding" class="hover:text-amber-600 transition-colors">Bank Data</Link>
        <i class="pi pi-chevron-right text-[9px]" />
        <span class="text-slate-500">Detail #{{ record.id }}</span>
    </div>
</template>
