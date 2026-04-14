<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import L from "leaflet";
import markerIcon from "leaflet/dist/images/marker-icon.png";
import markerIcon2x from "leaflet/dist/images/marker-icon-2x.png";
import markerShadow from "leaflet/dist/images/marker-shadow.png";
import "leaflet/dist/leaflet.css";
import UiSurface from "../../ui/UiSurface.vue";
import UiSectionHeader from "../../ui/UiSectionHeader.vue";

const props = defineProps({
    record: {
        type: Object,
        default: () => ({}),
    },
});

const mapContainer = ref(null);
const mapInstance = ref(null);
const imageError = ref(false);

const latitude = computed(() => {
    const value = Number(props.record?.latitude);
    return Number.isFinite(value) ? value : null;
});

const longitude = computed(() => {
    const value = Number(props.record?.longitude);
    return Number.isFinite(value) ? value : null;
});

const hasCoordinates = computed(() => latitude.value !== null && longitude.value !== null);

const defaultMarkerIcon = L.icon({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41],
});

onMounted(() => {
    if (!hasCoordinates.value || !mapContainer.value) return;

    mapInstance.value = L.map(mapContainer.value, { zoomControl: true }).setView([latitude.value, longitude.value], 15);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "&copy; OpenStreetMap contributors",
    }).addTo(mapInstance.value);

    L.marker([latitude.value, longitude.value], { icon: defaultMarkerIcon })
        .addTo(mapInstance.value)
        .bindPopup(props.record?.alamat ?? "Lokasi")
        .openPopup();
});

onBeforeUnmount(() => {
    if (mapInstance.value) mapInstance.value.remove();
});
</script>

<template>
    <div class="grid gap-4 lg:grid-cols-2">
        <UiSurface padding="none" class="overflow-hidden">
            <div class="border-b border-slate-100 bg-slate-50/70 px-4 py-3">
                <UiSectionHeader title="Foto" subtitle="Nampak properti" icon="pi pi-camera" />
            </div>

            <div class="relative w-full bg-slate-100" style="aspect-ratio: 16/9">
                <img
                    v-if="record.image_url && !imageError"
                    :src="record.image_url"
                    alt="Foto properti"
                    class="absolute inset-0 h-full w-full object-cover"
                    loading="lazy"
                    @error="imageError = true"
                />

                <div v-else class="absolute inset-0 flex items-center justify-center p-6">
                    <div class="flex flex-col items-center gap-2 text-center">
                        <div class="flex size-14 items-center justify-center rounded-full bg-slate-200">
                            <i class="pi pi-image text-xl text-slate-400" aria-hidden="true" />
                        </div>
                        <p class="text-pretty text-xs font-medium text-slate-500">Foto belum tersedia</p>
                    </div>
                </div>
            </div>
        </UiSurface>

        <UiSurface padding="none" class="overflow-hidden">
            <div class="flex items-start justify-between gap-3 border-b border-slate-100 bg-slate-50/70 px-4 py-3">
                <UiSectionHeader
                    title="Peta Lokasi"
                    :subtitle="hasCoordinates ? 'Koordinat tersedia' : 'Koordinat tidak tersedia'"
                    icon="pi pi-map-marker"
                />

                <a
                    v-if="hasCoordinates"
                    :href="`https://www.google.com/maps?q=${latitude},${longitude}`"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex h-9 items-center justify-center gap-2 rounded-[var(--radius-sm)] border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                >
                    <i class="pi pi-external-link text-[12px]" aria-hidden="true" />
                    Google Maps
                </a>

                <span
                    v-else
                    class="inline-flex h-9 items-center gap-2 rounded-[var(--radius-sm)] border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-500"
                >
                    <i class="pi pi-times-circle text-[12px]" aria-hidden="true" />
                    Tanpa koordinat
                </span>
            </div>

            <div v-if="hasCoordinates" ref="mapContainer" class="map-container w-full" />

            <div v-else class="map-container flex items-center justify-center p-6">
                <div class="flex flex-col items-center gap-2 text-center">
                    <div class="flex size-14 items-center justify-center rounded-full bg-slate-100">
                        <i class="pi pi-map text-xl text-slate-300" aria-hidden="true" />
                    </div>
                    <p class="text-pretty text-xs font-medium text-slate-500">Koordinat tidak tersedia</p>
                </div>
            </div>
        </UiSurface>
    </div>
</template>

<style scoped>
:deep(.leaflet-pane),
:deep(.leaflet-top),
:deep(.leaflet-bottom),
:deep(.leaflet-control) {
    z-index: 10 !important;
}

.map-container {
    height: 240px;
}

@media (min-width: 640px) {
    .map-container {
        height: 280px;
    }
}
</style>

