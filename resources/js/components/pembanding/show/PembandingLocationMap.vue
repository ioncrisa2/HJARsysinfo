<script setup>
import { computed } from "vue";
import { useSingleMarkerLeafletMap } from "../../../composables/useSingleMarkerLeafletMap";
import "leaflet/dist/leaflet.css";

const props = defineProps({
    latitude: { type: [String, Number], default: null },
    longitude: { type: [String, Number], default: null },
    popupText: { type: String, default: "Lokasi" },
});

const lat = computed(() => {
    const value = Number(props.latitude);
    return Number.isFinite(value) ? value : null;
});

const lng = computed(() => {
    const value = Number(props.longitude);
    return Number.isFinite(value) ? value : null;
});

const hasCoordinates = computed(() => lat.value !== null && lng.value !== null);
const popupText = computed(() => props.popupText || "Lokasi");
const { mapContainer } = useSingleMarkerLeafletMap({
    lat,
    lng,
    hasCoordinates,
    popupText,
    zoom: 16,
    attributionControl: true,
    tileOptions: {
        maxZoom: 19,
        attribution: "&copy; OpenStreetMap contributors",
    },
    invalidateDelay: 100,
    initDelay: 0,
});
</script>

<template>
    <div class="relative aspect-[4/3] overflow-hidden bg-slate-100">
        <div v-if="hasCoordinates" ref="mapContainer" class="h-full w-full" />

        <div v-else class="flex h-full flex-col items-center justify-center gap-2 p-6 text-center">
            <i class="pi pi-map text-3xl text-slate-300" aria-hidden="true" />
            <p class="text-sm font-bold text-slate-400">Tidak ada koordinat</p>
        </div>

    </div>
</template>

<style scoped>
:deep(.leaflet-pane),
:deep(.leaflet-top),
:deep(.leaflet-bottom),
:deep(.leaflet-control) {
    z-index: 10 !important;
}
</style>
