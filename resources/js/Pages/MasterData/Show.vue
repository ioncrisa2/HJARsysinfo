<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";
import DictionaryCrud from "../../components/master-data/DictionaryCrud.vue";

defineOptions({ layout: AppLayout });

const props = defineProps({
    category: { type: Object, required: true },
    items: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    can: { type: Object, default: () => ({}) },
});
</script>

<template>
    <Head :title="category.label" />

    <div class="space-y-5 py-3 sm:py-5">
        <header>
            <nav class="mb-3 flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-500" aria-label="Breadcrumb Master Data">
                <Link href="/app/master-data" class="rounded-sm hover:text-amber-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-500">
                    Master Data
                </Link>
                <i class="pi pi-chevron-right text-[9px] text-slate-300" aria-hidden="true" />
                <span class="text-slate-800" aria-current="page">{{ category.label }}</span>
            </nav>

            <div class="flex items-start gap-3">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
                    <i class="pi" :class="category.icon" aria-hidden="true" />
                </span>
                <div>
                    <h1 class="text-balance text-2xl font-black text-slate-900 sm:text-3xl">{{ category.label }}</h1>
                    <p class="mt-1 max-w-3xl text-pretty text-sm leading-6 text-slate-600">{{ category.description }}</p>
                </div>
            </div>
        </header>

        <DictionaryCrud :category="category" :items="items" :filters="filters" :can="can" />
    </div>
</template>
