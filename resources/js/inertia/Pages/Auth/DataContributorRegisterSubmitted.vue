<script setup>
import { Head, Link } from "@inertiajs/vue3";

defineProps({
    generatedEmail: { type: String, default: null },
    loginUrl: { type: String, required: true },
});
</script>

<template>
    <Head title="Request Terkirim" />

    <main class="flex min-h-dvh items-center justify-center bg-slate-100 px-4 py-8">
        <section class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-sm">
            <img :src="'/images/h-logo.jpg'" alt="KJPP HJAR" class="mx-auto h-14 w-14 rounded-xl object-cover" />
            <h1 class="mt-5 text-xl font-bold text-slate-950">Request registrasi terkirim</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Akun belum aktif. Super admin perlu melakukan konfirmasi dan accept terlebih dahulu.
            </p>
            <div v-if="generatedEmail" class="mt-4 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-left">
                <p class="text-xs font-semibold uppercase text-slate-500">Email login internal</p>
                <p class="mt-1 break-all font-mono text-sm font-bold text-slate-900">{{ generatedEmail }}</p>
            </div>
            <Link
                :href="loginUrl"
                class="mt-6 inline-flex min-h-11 items-center justify-center rounded-lg bg-slate-900 px-4 text-sm font-bold text-white hover:bg-slate-800"
            >
                Ke halaman login
            </Link>
        </section>
    </main>
</template>
