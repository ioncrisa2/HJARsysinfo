<script setup>
import UiButton from "./UiButton.vue";

const props = defineProps({
    ariaLabel: { type: String, required: true },
    variant: { type: String, default: "ghost" },
    icon: { type: String, required: true },
    loading: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
});

defineEmits(["click"]);
</script>

<template>
    <UiButton
        class="ui-hit px-0"
        type="button"
        :aria-label="props.ariaLabel"
        :variant="props.variant"
        size="sm"
        :icon="props.icon"
        :loading="props.loading"
        :disabled="props.disabled"
        @click="$emit('click', $event)"
    >
        <span class="sr-only">{{ props.ariaLabel }}</span>
    </UiButton>
</template>
