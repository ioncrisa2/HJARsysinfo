<script setup>
import { computed } from "vue";
import Drawer from "primevue/drawer";
import PembandingFilterFields from "./PembandingFilterFields.vue";

const props = defineProps({
    filters: { type: Object, required: true },
    options: { type: Object, default: () => ({}) },
    regencyOptions: { type: Array, default: () => [] },
    districtOptions: { type: Array, default: () => [] },
    villageOptions: { type: Array, default: () => [] },
    locationLoading: {
        type: Object,
        default: () => ({ regencies: false, districts: false, villages: false }),
    },
    hasActiveFilters: { type: Boolean, default: false },
    dateRange: { default: null },
    drawerVisible: { type: Boolean, default: false },
});

const emit = defineEmits(["submit", "reset", "update:dateRange", "update:drawerVisible"]);

const drawerModel = computed({
    get: () => props.drawerVisible,
    set: (value) => emit("update:drawerVisible", value),
});

const fieldProps = computed(() => ({
    filters: props.filters,
    options: props.options,
    regencyOptions: props.regencyOptions,
    districtOptions: props.districtOptions,
    villageOptions: props.villageOptions,
    locationLoading: props.locationLoading,
    hasActiveFilters: props.hasActiveFilters,
    dateRange: props.dateRange,
}));
</script>

<template>
    <Drawer
        v-model:visible="drawerModel"
        position="left"
        header="Filter Pencarian"
        :style="{ width: 'min(92vw, 380px)' }"
    >
        <form class="grid gap-4 px-1 py-2" @submit.prevent="emit('submit')">
            <PembandingFilterFields
                v-bind="fieldProps"
                @update:date-range="emit('update:dateRange', $event)"
                @submit="emit('submit')"
                @reset="emit('reset')"
            />
        </form>
    </Drawer>
</template>
