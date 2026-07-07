<script setup>
import { computed } from "vue";

const props = defineProps({
    as: { type: String, default: "div" },
    variant: { type: String, default: "surface" }, // surface | inset | plain
    padding: { type: String, default: "md" }, // none | sm | md | lg
    hoverable: { type: Boolean, default: false },
});

const paddingClass = computed(() => {
    if (props.padding === "none") return "";
    if (props.padding === "sm") return "p-3";
    if (props.padding === "lg") return "p-6";
    return "p-4";
});

const variantClass = computed(() => {
    if (props.variant === "plain") return "";
    if (props.variant === "inset")
        return "rounded-[var(--radius-lg)] border border-slate-200 bg-slate-50/70";
    return "rounded-[var(--radius-lg)] border border-slate-200 bg-white shadow-[var(--shadow-surface)]";
});

const hoverClass = computed(() => (props.hoverable ? "transition hover:shadow-[var(--shadow-surface-hover)]" : ""));
</script>

<template>
    <component :is="props.as" :class="[variantClass, paddingClass, hoverClass]">
        <slot />
    </component>
</template>

