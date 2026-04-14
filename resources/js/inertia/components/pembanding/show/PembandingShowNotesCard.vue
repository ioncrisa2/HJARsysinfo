<script setup>
import UiSurface from "../../ui/UiSurface.vue";
import UiSectionHeader from "../../ui/UiSectionHeader.vue";

defineProps({
    note: {
        type: String,
        default: "",
    },
});
</script>

<template>
    <UiSurface v-if="note" variant="inset" padding="none" class="overflow-hidden">
        <div class="border-b border-slate-200/60 px-4 py-3">
            <UiSectionHeader title="Catatan" subtitle="Informasi tambahan dari input data" icon="pi pi-file-edit" />
        </div>

        <div class="p-4">
            <p class="text-pretty whitespace-pre-line text-sm leading-relaxed text-slate-800">
                {{ note }}
            </p>
        </div>
    </UiSurface>
</template>

