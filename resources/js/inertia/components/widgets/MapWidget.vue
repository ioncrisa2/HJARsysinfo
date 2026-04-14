<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import Select from "primevue/select";
import InputText from "primevue/inputtext";
import "leaflet/dist/leaflet.css";
import "leaflet.markercluster/dist/MarkerCluster.css";
import "leaflet.markercluster/dist/MarkerCluster.Default.css";
import L from "leaflet";
import "leaflet.markercluster";
import markerIcon from "leaflet/dist/images/marker-icon.png";
import markerIcon2x from "leaflet/dist/images/marker-icon-2x.png";
import markerShadow from "leaflet/dist/images/marker-shadow.png";

const props = defineProps({
    points: {
        type: Array,
        default: () => [],
    },
    listingOptions: {
        type: Array,
        default: () => [],
    },
    height: {
        type: String,
        default: "460px",
    },
});

const mapContainer = ref(null);
const mapInstance = ref(null);
const markerLayer = ref(null);
const searchLayer = ref(null);
const listingFilter = ref(null);
const searchLat = ref("");
const searchLng = ref("");
const homeBounds = ref(null);
const homeCenter = ref(null);
const homeZoom = ref(null);
const cursorLat = ref(null);
const cursorLng = ref(null);

const selectedListingId = computed(() => {
    if (listingFilter.value === null || listingFilter.value === undefined || listingFilter.value === "") {
        return null;
    }

    const parsed = Number(listingFilter.value);
    return Number.isFinite(parsed) ? parsed : null;
});

const filteredPoints = computed(() =>
    props.points.filter((p) =>
        selectedListingId.value === null || Number(p.jenis_listing_id ?? 0) === selectedListingId.value
    )
);

const hasSearchPoint = computed(() =>
    searchLat.value !== "" &&
    searchLng.value !== "" &&
    Number.isFinite(Number(searchLat.value)) &&
    Number.isFinite(Number(searchLng.value))
);

const cursorCoordinates = computed(() => {
    if (!Number.isFinite(cursorLat.value) || !Number.isFinite(cursorLng.value)) {
        return "Gerakkan cursor di atas peta";
    }

    return `${Number(cursorLat.value).toFixed(6)}, ${Number(cursorLng.value).toFixed(6)}`;
});

const defaultMarkerIcon = L.icon({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41],
});

const redMarkerIcon = L.icon({
    iconRetinaUrl: "https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png",
    iconUrl: "https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png",
    shadowUrl: markerShadow,
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41],
});

const formatCurrency = (value) =>
    new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(Number(value ?? 0));

const escapeHtml = (value) =>
    String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#39;");

const ensureMap = () => {
    if (mapInstance.value || !mapContainer.value) return;

    mapInstance.value = L.map(mapContainer.value, { zoomControl: true });

    const osm = L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "&copy; OpenStreetMap contributors",
    });
    const satellite = L.tileLayer("https://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}", {
        subdomains: ["mt0", "mt1", "mt2", "mt3"],
        attribution: "(c) Google Satellite",
    });

    satellite.addTo(mapInstance.value);
    L.control.layers({ "OSM Basic": osm, Satellite: satellite }).addTo(mapInstance.value);

    // Marker cluster group instead of plain layerGroup
    markerLayer.value = L.markerClusterGroup({
        chunkedLoading: true,
        maxClusterRadius: 60,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
    }).addTo(mapInstance.value);

    searchLayer.value = L.layerGroup().addTo(mapInstance.value);
    mapInstance.value.setView([-2.5489, 118.0149], 5);

    mapInstance.value.on("mousemove", (event) => {
        cursorLat.value = event.latlng.lat;
        cursorLng.value = event.latlng.lng;
    });

    mapInstance.value.on("mouseout", () => {
        cursorLat.value = null;
        cursorLng.value = null;
    });
};

const resetMapZoom = () => {
    ensureMap();
    if (!mapInstance.value) return;

    if (homeBounds.value) {
        mapInstance.value.fitBounds(homeBounds.value, { padding: [30, 30], maxZoom: 14 });
        return;
    }

    if (homeCenter.value) {
        mapInstance.value.setView(homeCenter.value, homeZoom.value ?? 5);
    }
};

const renderMap = () => {
    ensureMap();
    if (!mapInstance.value || !markerLayer.value) return;

    markerLayer.value.clearLayers();

    if (filteredPoints.value.length === 0) {
        homeBounds.value = null;
        homeCenter.value = [-2.5489, 118.0149];
        homeZoom.value = 5;
        mapInstance.value.setView(homeCenter.value, homeZoom.value);
        return;
    }

    const bounds = [];

    filteredPoints.value.forEach((point) => {
        if (!Number.isFinite(point.latitude) || !Number.isFinite(point.longitude)) return;

        const marker = L.marker([point.latitude, point.longitude], { icon: defaultMarkerIcon });
        const imageUrl = point.image_url || "https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=640&q=60";

        marker.bindPopup(`
            <div style="min-width:240px;max-width:260px;font-family:ui-sans-serif,system-ui">
                <div style="height:130px;border-radius:10px;overflow:hidden;background:#f8fafc;margin-bottom:10px;position:relative">
                    <span style="position:absolute;right:8px;top:8px;background:#d97706;color:white;font-weight:700;font-size:10px;padding:3px 8px;border-radius:999px;z-index:1">
                        ${escapeHtml(point.jenis_listing ?? "")}
                    </span>
                    <img src="${escapeHtml(imageUrl)}" style="width:100%;height:100%;object-fit:cover" loading="lazy" />
                </div>
                <div style="font-weight:700;font-size:14px;color:#0f172a;margin-bottom:3px;line-height:1.3">
                    ${escapeHtml(point.alamat ?? "Tanpa alamat")}
                </div>
                <div style="font-size:11px;color:#94a3b8;margin-bottom:10px">
                    ${point.latitude.toFixed(6)}, ${point.longitude.toFixed(6)}
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between">
                    <span style="color:#d97706;font-weight:700;font-size:13px">${formatCurrency(point.harga)}</span>
                    <a href="${escapeHtml(point.detail_url ?? '#')}" style="background:#0f172a;color:white;font-size:11px;font-weight:600;padding:5px 12px;border-radius:6px;text-decoration:none">
                        Detail ->
                    </a>
                </div>
            </div>
        `.trim());

        markerLayer.value.addLayer(marker);
        bounds.push([point.latitude, point.longitude]);
    });

    if (bounds.length === 1) {
        homeBounds.value = null;
        homeCenter.value = bounds[0];
        homeZoom.value = 14;
        mapInstance.value.setView(homeCenter.value, homeZoom.value);
        return;
    }

    homeBounds.value = L.latLngBounds(bounds);
    homeCenter.value = null;
    homeZoom.value = null;
    mapInstance.value.fitBounds(homeBounds.value, { padding: [30, 30], maxZoom: 14 });
};

const goToSearch = () => {
    if (!hasSearchPoint.value) return;
    ensureMap();
    if (!searchLayer.value) return;

    searchLayer.value.clearLayers();
    const lat = Number(searchLat.value);
    const lng = Number(searchLng.value);

    const marker = L.marker([lat, lng], { icon: redMarkerIcon });
    marker.bindPopup(`
        <strong>Titik Pencarian</strong><br/>
        Lat: ${lat.toFixed(6)}<br/>
        Lng: ${lng.toFixed(6)}
    `);
    searchLayer.value.addLayer(marker);
    mapInstance.value?.setView([lat, lng], 14);
    marker.openPopup();
};

watch([searchLat, searchLng], () => {
    if (!hasSearchPoint.value && searchLayer.value) searchLayer.value.clearLayers();
});

watch(() => props.points, () => renderMap(), { deep: true });
watch(filteredPoints, () => renderMap());

onMounted(() => renderMap());

onBeforeUnmount(() => {
    if (mapInstance.value) {
        mapInstance.value.remove();
        mapInstance.value = null;
        markerLayer.value = null;
        searchLayer.value = null;
    }
});
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <!-- Toolbar -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                    <i class="pi pi-map text-amber-500 text-xs" />
                    Peta Sebaran Data
                </div>
                <Select
                    v-model="listingFilter"
                    :options="listingOptions"
                    option-label="label"
                    option-value="value"
                    placeholder="Semua Listing"
                    class="w-48"
                    show-clear
                />
                <span class="text-xs text-slate-400">{{ filteredPoints.length }} titik</span>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <InputText v-model="searchLat" placeholder="Latitude" class="w-28 text-xs" />
                <InputText v-model="searchLng" placeholder="Longitude" class="w-28 text-xs" />
                <button
                    type="button"
                    class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-40"
                    :disabled="!hasSearchPoint"
                    @click="goToSearch"
                >
                    <i class="pi pi-search mr-1 text-[10px]" />
                    Zoom
                </button>
                <button
                    type="button"
                    class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900"
                    @click="resetMapZoom"
                >
                    <i class="pi pi-search-minus mr-1 text-[10px]" />
                    Reset Zoom
                </button>
            </div>
        </div>

        <!-- Map -->
        <div class="relative">
            <div ref="mapContainer" class="w-full" :style="{ height }" />

            <div class="pointer-events-none absolute bottom-3 left-3 z-20 rounded-lg border border-slate-200 bg-white px-3 py-1.5 shadow-sm">
                <p class="text-[10px] font-semibold text-slate-500">Koordinat cursor</p>
                <p class="ui-tabular text-xs font-medium text-slate-700">{{ cursorCoordinates }}</p>
            </div>

            <!-- Empty state -->
            <div
                v-if="filteredPoints.length === 0"
                class="pointer-events-none absolute inset-0 flex items-center justify-center"
            >
                <div class="rounded-xl border border-slate-200 bg-white px-6 py-4 text-center shadow-sm">
                    <i class="pi pi-map-marker text-2xl text-slate-300" />
                    <p class="mt-2 text-sm font-semibold text-slate-500">Tidak ada titik untuk filter ini</p>
                    <p class="text-xs text-slate-400">Coba ubah filter jenis listing</p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="border-t border-slate-100 px-4 py-2.5 text-xs text-slate-400">
            Menampilkan semua data terbaru. Marker dikelompokkan otomatis saat zoom out.
        </div>
    </div>
</template>

<style>
/* Amber cluster theme to match app color */
.marker-cluster-small { background-color: rgba(245, 158, 11, 0.25); }
.marker-cluster-small div { background-color: rgba(245, 158, 11, 0.6); color: white; font-weight: 700; }
.marker-cluster-medium { background-color: rgba(245, 158, 11, 0.35); }
.marker-cluster-medium div { background-color: rgba(245, 158, 11, 0.75); color: white; font-weight: 700; }
.marker-cluster-large { background-color: rgba(245, 158, 11, 0.45); }
.marker-cluster-large div { background-color: rgba(245, 158, 11, 0.9); color: white; font-weight: 700; }
</style>
