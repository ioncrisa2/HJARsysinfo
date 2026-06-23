<script setup>
import "leaflet/dist/leaflet.css";
import "leaflet.markercluster/dist/MarkerCluster.css";
import "leaflet.markercluster/dist/MarkerCluster.Default.css";
import L from "leaflet";
import { useClusteredLeafletMap } from "../../../composables/useClusteredLeafletMap";

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

const buildMarker = (point) => {
    const icon = L.icon({
        iconUrl: point.icon,
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowUrl: "https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png",
        shadowSize: [41, 41],
    });

    return L.marker([point.lat, point.lng], { icon }).bindPopup(`
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
};

const { mapContainer } = useClusteredLeafletMap({
    markers: () => props.markers,
    buildMarker,
    defaultCenter: [-2.5489, 118.0149],
    defaultZoom: 5,
    fitBoundsOptions: { padding: [20, 20], maxZoom: 15 },
    layers: null,
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
