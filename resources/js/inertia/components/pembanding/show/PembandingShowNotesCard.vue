<script setup>
defineProps({
    note: {
        type: String,
        default: "",
    },
});
</script>

<template>
    <div
        v-if="note"
        class="overflow-hidden rounded-2xl border border-amber-100 bg-amber-50 shadow-sm"
    >
        <!-- Header -->
        <div class="flex items-center gap-2.5 border-b border-amber-100 px-4 py-3">
            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-200">
                <i class="pi pi-file-edit text-amber-700" style="font-size: 12px" />
            </div>
            <span class="text-sm font-bold text-amber-800">Catatan Tambahan</span>
        </div>

        <!-- Note body -->
        <p class="whitespace-pre-line px-5 py-4 text-sm leading-relaxed text-amber-900">
            {{ note }}
        </p>
    </div>
</template>
