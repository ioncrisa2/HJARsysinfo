<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";
import UiSurface from "../../components/ui/UiSurface.vue";

defineOptions({ layout: AppLayout });

const props = defineProps({
    categories: { type: Array, default: () => [] },
});

const categoryUrl = (type) => `/app/master-data/${type}`;
const formatNumber = (value) => new Intl.NumberFormat("id-ID").format(Number(value ?? 0));
</script>

<template>
    <Head title="Master Data" />

    <div class="space-y-5 py-3 sm:py-5">
        <header class="max-w-3xl">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-amber-700">Referensi Data Pembanding</p>
            <h1 class="mt-2 text-balance text-2xl font-black text-slate-900 sm:text-3xl">Master Data</h1>
            <p class="mt-2 text-pretty text-sm leading-6 text-slate-600">
                Pilih kategori yang akan dikelola. Data nonaktif tidak tersedia untuk input baru, tetapi tetap dipertahankan pada riwayat Data Pembanding.
            </p>
        </header>

        <section aria-labelledby="master-data-categories">
            <h2 id="master-data-categories" class="sr-only">Kategori Master Data</h2>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <UiSurface
                    v-for="category in props.categories"
                    :key="category.type"
                    padding="none"
                    class="group overflow-hidden transition hover:border-amber-300"
                >
                    <div class="flex h-full flex-col p-4 sm:p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex min-w-0 items-start gap-3">
                                <span class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
                                    <i class="pi" :class="category.icon" aria-hidden="true" />
                                </span>
                                <div class="min-w-0">
                                    <h3 class="text-base font-bold text-slate-900">{{ category.label }}</h3>
                                    <p class="mt-1 text-pretty text-xs leading-5 text-slate-500">{{ category.description }}</p>
                                </div>
                            </div>
                            <span class="ui-tabular shrink-0 text-lg font-black text-slate-900">
                                {{ formatNumber(category.stats?.total) }}
                            </span>
                        </div>

                        <dl class="mt-5 grid grid-cols-2 gap-2 text-xs">
                            <div class="rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-2">
                                <dt class="font-semibold text-emerald-700">Aktif</dt>
                                <dd class="ui-tabular mt-0.5 font-black text-emerald-900">{{ formatNumber(category.stats?.active) }}</dd>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                <dt class="font-semibold text-slate-500">Nonaktif</dt>
                                <dd class="ui-tabular mt-0.5 font-black text-slate-800">{{ formatNumber(category.stats?.inactive) }}</dd>
                            </div>
                        </dl>

                        <Link
                            :href="categoryUrl(category.type)"
                            class="mt-4 inline-flex min-h-11 items-center justify-between rounded-lg border border-slate-200 px-3 text-sm font-bold text-slate-700 transition hover:border-amber-300 hover:bg-amber-50 hover:text-amber-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-500"
                            :aria-label="`Buka ${category.label}`"
                        >
                            <span>Buka kategori</span>
                            <i class="pi pi-arrow-right text-xs" aria-hidden="true" />
                        </Link>
                    </div>
                </UiSurface>
            </div>
        </section>
    </div>
</template>
