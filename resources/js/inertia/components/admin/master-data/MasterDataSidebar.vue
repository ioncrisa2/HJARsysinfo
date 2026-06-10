<script setup>
import { Link } from "@inertiajs/vue3";
import UiSurface from "../../ui/UiSurface.vue";

const props = defineProps({
    resources: { type: Array, required: true },
    currentResource: { type: String, default: null },
});

const resourceUrl = (resource) => `/admin/master-data/${resource}`;
</script>

<template>
    <aside class="lg:col-span-3">
        <UiSurface padding="none" class="overflow-hidden">
            <div class="border-b border-slate-100 bg-slate-50 px-4 py-4">
                <p class="text-balance text-sm font-bold text-slate-900">Menu Master Data</p>
                <p class="mt-1 text-pretty text-xs text-slate-500">Pilih kategori referensi.</p>
            </div>

            <nav class="p-2">
                <Link
                    v-for="res in resources"
                    :key="res.slug"
                    :href="resourceUrl(res.slug)"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold"
                    :class="currentResource === res.slug ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-50'"
                >
                    <i :class="res.icon" class="shrink-0 text-xs" />
                    <span class="truncate">{{ res.label }}</span>
                </Link>
            </nav>
        </UiSurface>
    </aside>
</template>
