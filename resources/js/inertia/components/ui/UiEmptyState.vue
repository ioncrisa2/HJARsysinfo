<script setup>
const props = defineProps({
    title: { type: String, required: true },
    description: { type: String, default: "" },
    icon: { type: String, default: "pi pi-inbox" },
});
</script>

<template>
    <div class="flex flex-col items-center justify-center gap-3 rounded-[var(--radius-lg)] border border-dashed border-slate-200 bg-slate-50 py-14 text-center">
        <i :class="props.icon" class="text-3xl text-slate-300" />
        <div class="space-y-1">
            <p class="text-sm font-semibold text-slate-600">{{ props.title }}</p>
            <p v-if="props.description" class="text-xs text-slate-500">
                {{ props.description }}
            </p>
        </div>
        <div class="pt-2">
            <slot name="actions" />
        </div>
    </div>
</template>

