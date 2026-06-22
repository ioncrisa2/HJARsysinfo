<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from "vue";
import L from "leaflet";
import markerIcon from "leaflet/dist/images/marker-icon.png";
import markerIcon2x from "leaflet/dist/images/marker-icon-2x.png";
import markerShadow from "leaflet/dist/images/marker-shadow.png";
import "leaflet/dist/leaflet.css";

const props = defineProps({
    latitude: { type: [String, Number], default: null },
    longitude: { type: [String, Number], default: null },
    popupText: { type: String, default: "Lokasi" },
});

const mapContainer = ref(null);
const mapInstance = ref(null);

const lat = computed(() => {
    const value = Number(props.latitude);
    return Number.isFinite(value) ? value : null;
});

const lng = computed(() => {
    const value = Number(props.longitude);
    return Number.isFinite(value) ? value : null;
});

const hasCoordinates = computed(() => lat.value !== null && lng.value !== null);

const defaultMarkerIcon = L.icon({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41],
});

const initMap = async () => {
    if (!hasCoordinates.value || !mapContainer.value || mapInstance.value) {
        return;
    }

    await nextTick();

    mapInstance.value = L.map(mapContainer.value, {
        zoomControl: true,
        attributionControl: true,
    }).setView([lat.value, lng.value], 16);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: "&copy; OpenStreetMap contributors",
    }).addTo(mapInstance.value);

    L.marker([lat.value, lng.value], { icon: defaultMarkerIcon })
        .addTo(mapInstance.value)
        .bindPopup(props.popupText || "Lokasi");

    window.setTimeout(() => {
        mapInstance.value?.invalidateSize();
    }, 100);
};

onMounted(initMap);

onBeforeUnmount(() => {
    if (mapInstance.value) {
        mapInstance.value.remove();
        mapInstance.value = null;
    }
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
