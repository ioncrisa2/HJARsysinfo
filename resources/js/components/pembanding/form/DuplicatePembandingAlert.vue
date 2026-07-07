<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    message: {
        type: String,
        default: "",
    },
    duplicate: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <div
        v-if="message"
        role="alert"
        class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950"
    >
        <div class="flex items-start gap-3">
            <i class="pi pi-exclamation-triangle mt-0.5" aria-hidden="true" />
            <div class="min-w-0 flex-1">
                <p class="font-semibold">Data identik tidak disimpan</p>
                <p class="mt-1 text-pretty">{{ message }}</p>
                <Link
                    v-if="duplicate?.url"
                    :href="duplicate.url"
                    class="mt-2 inline-flex min-h-11 items-center font-semibold text-amber-900 underline decoration-amber-500 underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-600"
                >
                    Buka record #{{ duplicate.id }}
                </Link>
            </div>
        </div>
    </div>
</template>
