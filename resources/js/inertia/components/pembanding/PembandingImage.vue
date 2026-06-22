<script setup>
import { computed, ref, watch } from "vue";

const props = defineProps({
    src: {
        type: String,
        default: null,
    },
    alt: {
        type: String,
        default: "Foto properti",
    },
    imageClass: {
        type: String,
        default: "h-full w-full object-cover",
    },
    placeholderLabel: {
        type: String,
        default: "Foto tidak tersedia",
    },
});

const failed = ref(false);

watch(() => props.src, () => {
    failed.value = false;
});

const hasImage = computed(() => Boolean(props.src) && !failed.value);
</script>

<template>
    <img
        v-if="hasImage"
        :src="src"
        :alt="alt"
        :class="imageClass"
        loading="lazy"
        @error="failed = true"
    />

    <div v-else class="flex h-full w-full items-center justify-center bg-slate-100 p-3">
        <div class="flex flex-col items-center gap-2 text-center text-slate-400">
            <span class="flex size-10 items-center justify-center rounded-full bg-white shadow-sm">
                <i class="pi pi-image text-base" aria-hidden="true" />
            </span>
            <span class="text-[11px] font-semibold leading-tight">{{ placeholderLabel }}</span>
        </div>
    </div>
</template>
