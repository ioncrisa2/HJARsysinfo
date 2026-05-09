<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from "vue";
import "leaflet/dist/leaflet.css";
import "leaflet.markercluster/dist/MarkerCluster.css";
import "leaflet.markercluster/dist/MarkerCluster.Default.css";
import L from "leaflet";
import "leaflet.markercluster";

const props = defineProps({
    markers: {
        type: Array,
        default: () => [],
    },
    height: {
        type: String,
        default: "400px",
    },
});

const mapContainer = ref(null);
const mapInstance = ref(null);
const markerLayer = ref(null);

const initMap = () => {
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
    L.control.layers({ "Satellite": satellite, "OSM Basic": osm }).addTo(mapInstance.value);

    markerLayer.value = L.markerClusterGroup({
        chunkedLoading: true,
        maxClusterRadius: 60,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
    }).addTo(mapInstance.value);

    renderMarkers();
};

const renderMarkers = () => {
    if (!mapInstance.value || !markerLayer.value) return;

    markerLayer.value.clearLayers();

    if (props.markers.length === 0) {
        mapInstance.value.setView([-2.5489, 118.0149], 5);
        return;
    }

    const bounds = [];

    props.markers.forEach((point) => {
        const icon = L.icon({
            iconUrl: point.icon,
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowUrl: "https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png",
            shadowSize: [41, 41],
        });

        const marker = L.marker([point.lat, point.lng], { icon });
        
        marker.bindPopup(`
            <div style="width: 240px; font-family: 'Inter', sans-serif; padding: 5px;">
                <div style="border-radius: 8px; overflow: hidden; margin-bottom: 10px; position: relative;">
                    <img src="${point.img_url}" style="width: 100%; height: 140px; object-fit: cover;">
                    <span style="position: absolute; top: 8px; right: 8px; background: ${point.badge_color}; color: white;
                        padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase;">
                        ${point.listing_name}
                    </span>
                </div>
                <div style="font-weight: 700; font-size: 14px; color: #1e293b; margin-bottom: 4px;">
                    ${point.alamat}
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px; border-top: 1px solid #f1f5f9; padding-top: 8px;">
                    <span style="font-size: 11px; color: #64748b;">ID: #${point.id}</span>
                    <a href="/admin/pembanding/${point.id}/edit" style="color: #3b82f6; font-size: 12px; font-weight: 600; text-decoration: none;">
                        Edit &rarr;
                    </a>
                </div>
            </div>
        `);

        markerLayer.value.addLayer(marker);
        bounds.push([point.lat, point.lng]);
    });

    if (bounds.length > 0) {
        mapInstance.value.fitBounds(bounds, { padding: [20, 20], maxZoom: 15 });
    }
};

onMounted(() => {
    initMap();
});

watch(() => props.markers, () => renderMarkers(), { deep: true });

onBeforeUnmount(() => {
    if (mapInstance.value) {
        mapInstance.value.remove();
        mapInstance.value = null;
    }
});
</script>

<template>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-white">
            <div>
                <h3 class="font-bold text-slate-800">Peta Sebaran Data</h3>
                <p class="text-xs text-slate-500">Menampilkan {{ markers.length }} lokasi properti terbaru</p>
            </div>
            <div class="h-8 w-8 rounded-lg bg-red-50 flex items-center justify-center text-red-600">
                <i class="pi pi-map-marker" />
            </div>
        </div>
        <div ref="mapContainer" :style="{ height }" class="w-full z-10" />
    </div>
</template>

<style>
.marker-cluster-small { background-color: rgba(59, 130, 246, 0.2); }
.marker-cluster-small div { background-color: rgba(59, 130, 246, 0.6); color: white; font-weight: 700; }
.marker-cluster-medium { background-color: rgba(59, 130, 246, 0.3); }
.marker-cluster-medium div { background-color: rgba(59, 130, 246, 0.75); color: white; font-weight: 700; }
.marker-cluster-large { background-color: rgba(59, 130, 246, 0.4); }
.marker-cluster-large div { background-color: rgba(59, 130, 246, 0.9); color: white; font-weight: 700; }
</style>
