<script setup>
const props = defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: "" },
    icon: { type: String, default: "" }, // primeicons class e.g. "pi pi-chart-line"
});
</script>

<template>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="flex items-center gap-2">
                <i v-if="props.icon" :class="props.icon" class="text-[12px] text-amber-600" />
                <h2 class="truncate text-balance text-sm font-semibold text-slate-900">{{ props.title }}</h2>
            </div>
            <p v-if="props.subtitle" class="mt-0.5 text-pretty text-xs text-slate-500">
                {{ props.subtitle }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
