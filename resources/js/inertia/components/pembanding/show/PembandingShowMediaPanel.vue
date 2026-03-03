<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import L from "leaflet";
import markerIcon from "leaflet/dist/images/marker-icon.png";
import markerIcon2x from "leaflet/dist/images/marker-icon-2x.png";
import markerShadow from "leaflet/dist/images/marker-shadow.png";
import "leaflet/dist/leaflet.css";

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

    mapInstance.value = L.map(mapContainer.value, { zoomControl: true }).setView(
        [latitude.value, longitude.value],
        15
    );

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

        <!-- ── Foto Properti ────────────────────────────── -->
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <!-- Panel header -->
            <div class="flex items-center gap-2 border-b border-slate-100 bg-slate-50/70 px-4 py-3">
                <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-amber-100">
                    <i class="pi pi-camera text-amber-600" style="font-size: 11px" />
                </div>
                <span class="text-sm font-semibold text-slate-700">Nampak Properti</span>
            </div>

            <!-- Image -->
            <div class="relative w-full bg-slate-100" style="aspect-ratio: 16/9">
                <img
                    v-if="record.image_url && !imageError"
                    :src="record.image_url"
                    alt="Foto properti"
                    class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 hover:scale-105"
                    loading="lazy"
                    @error="imageError = true"
                />
                <!-- Empty state -->
                <div
                    v-else
                    class="absolute inset-0 flex flex-col items-center justify-center gap-3"
                >
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-slate-200">
                        <i class="pi pi-image text-2xl text-slate-400" />
                    </div>
                    <p class="text-xs font-medium text-slate-400">Foto belum tersedia</p>
                </div>
            </div>
        </div>

        <!-- ── Peta Lokasi ─────────────────────────────── -->
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <!-- Panel header -->
            <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/70 px-4 py-3">
                <div class="flex items-center gap-2">
                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-amber-100">
                        <i class="pi pi-map-marker text-amber-600" style="font-size: 11px" />
                    </div>
                    <span class="text-sm font-semibold text-slate-700">Peta Lokasi</span>
                </div>

                <a
                    v-if="hasCoordinates"
                    :href="`https://www.google.com/maps?q=${latitude},${longitude}`"
                    target="_blank"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm transition hover:border-emerald-300 hover:text-emerald-600 hover:bg-emerald-50"
                >
                    <i class="pi pi-external-link text-[10px]" />
                    <span class="hidden sm:inline">Google</span> Maps
                </a>
                <span v-else class="inline-flex items-center gap-1 rounded-full border border-slate-100 bg-slate-50 px-2.5 py-0.5 text-[11px] font-medium text-slate-400">
                    <i class="pi pi-times-circle text-[10px]" />
                    Tanpa koordinat
                </span>
            </div>

            <!-- Map -->
            <div ref="mapContainer" class="map-container w-full" />

            <!-- No coordinates placeholder -->
            <div
                v-if="!hasCoordinates"
                class="flex flex-col items-center justify-center gap-3 py-16"
            >
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-slate-100">
                    <i class="pi pi-map text-2xl text-slate-300" />
                </div>
                <p class="text-xs font-medium text-slate-400">Koordinat tidak tersedia</p>
            </div>
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

.map-container {
    height: 240px;
}

@media (min-width: 640px) {
    .map-container {
        height: 280px;
    }
}

@media (min-width: 1024px) {
    .map-container {
        height: calc(100% - 49px);
        min-height: 280px;
    }
}
</style>
