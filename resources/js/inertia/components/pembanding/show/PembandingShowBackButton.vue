<script setup>
import { Link } from "@inertiajs/vue3";
</script>

<template>
    <div class="flex items-center justify-between pb-2 pt-1">
        <Link
            href="/home/pembanding"
            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900"
        >
            <i class="pi pi-arrow-left text-xs" />
            Kembali ke Bank Data
        </Link>

        <p class="text-xs text-slate-300 hidden sm:block">
            Data Pembanding &mdash; Sistem Penilaian Properti
        </p>
    </div>
</template>
