<script setup>
import { computed, toRef } from "vue";
import { useMinLoadingState } from "../../composables/useMinLoadingState";

const props = defineProps({
    type: { type: String, default: "button" },
    variant: { type: String, default: "secondary" }, // primary | secondary | ghost | danger
    size: { type: String, default: "md" }, // sm | md
    icon: { type: String, default: "" }, // primeicons class e.g. "pi pi-plus"
    loading: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(["click"]);

const loadingRef = toRef(props, "loading");
const { show: showSpinner } = useMinLoadingState(loadingRef, { delayMs: 200, minMs: 350 });

const isDisabled = computed(() => props.disabled || props.loading);

const sizeClass = computed(() => {
    if (props.size === "sm") return "h-9 px-3 text-xs";
    return "h-10 px-4 text-sm";
});

const variantClass = computed(() => {
    if (props.variant === "primary") {
        return "bg-amber-500 text-white hover:bg-amber-600";
    }
    if (props.variant === "ghost") {
        return "bg-transparent text-slate-700 hover:bg-slate-100";
    }
    if (props.variant === "danger") {
        return "bg-red-500 text-white hover:bg-red-600";
    }
    return "border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300";
});

const spinnerClass = computed(() => {
    if (props.variant === "primary" || props.variant === "danger") {
        return "border-white/70 border-t-white";
    }
    return "border-slate-400/60 border-t-slate-700";
});
</script>

<template>
    <button
        :type="props.type"
        :disabled="isDisabled"
        class="inline-flex select-none items-center justify-center gap-2 rounded-[var(--radius-sm)] font-semibold transition
            disabled:cursor-not-allowed disabled:opacity-60"
        :class="[sizeClass, variantClass]"
        @click="(e) => emit('click', e)"
    >
        <span v-if="showSpinner" class="inline-flex items-center justify-center">
            <span class="h-4 w-4 animate-spin rounded-full border-2" :class="spinnerClass" />
        </span>
        <i v-else-if="props.icon" :class="props.icon" class="text-[12px]" />

        <span class="truncate">
            <slot />
        </span>
    </button>
</template>
