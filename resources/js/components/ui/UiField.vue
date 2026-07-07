<script setup>
const props = defineProps({
    id: { type: String, required: true },
    label: { type: String, required: true },
    required: { type: Boolean, default: false },
    help: { type: String, default: "" },
    error: { type: String, default: "" },
});
</script>

<template>
    <div class="space-y-1.5">
        <label :for="props.id" class="block text-xs font-semibold text-slate-600">
            {{ props.label }}
            <span v-if="props.required" class="text-red-500" aria-hidden="true">*</span>
        </label>

        <div data-focus-group>
            <slot />
        </div>

        <p v-if="props.error" class="text-pretty text-xs font-medium text-red-600">
            {{ props.error }}
        </p>
        <p v-else-if="props.help" class="text-pretty text-xs text-slate-500">
            {{ props.help }}
        </p>
    </div>
</template>
