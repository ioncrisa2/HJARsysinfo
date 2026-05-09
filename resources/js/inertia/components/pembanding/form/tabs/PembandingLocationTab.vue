<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import Textarea from "primevue/textarea";
import UiSectionHeader from "../../../ui/UiSectionHeader.vue";
import UiField from "../../../ui/UiField.vue";
import UiSurface from "../../../ui/UiSurface.vue";
import L from "leaflet";
import markerIcon from "leaflet/dist/images/marker-icon.png";
import markerIcon2x from "leaflet/dist/images/marker-icon-2x.png";
import markerShadow from "leaflet/dist/images/marker-shadow.png";
import "leaflet/dist/leaflet.css";

const props = defineProps({
    form: { type: Object, required: true },
    options: { type: Object, default: () => ({}) },
    regencyOptions: { type: Array, default: () => [] },
    districtOptions: { type: Array, default: () => [] },
    villageOptions: { type: Array, default: () => [] },
});

const emit = defineEmits(["prev", "next"]);

const mapContainer = ref(null);
const mapInstance = ref(null);
const markerInstance = ref(null);

const parsedLat = computed(() => {
    const v = Number(props.form.latitude);
    return Number.isFinite(v) && v !== 0 ? v : null;
});

const parsedLng = computed(() => {
    const v = Number(props.form.longitude);
    return Number.isFinite(v) && v !== 0 ? v : null;
});

const hasCoords = computed(() => parsedLat.value !== null && parsedLng.value !== null);

const defaultIcon = L.icon({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41],
});

const initMap = () => {
    if (!mapContainer.value || mapInstance.value || !hasCoords.value) return;
    mapInstance.value = L.map(mapContainer.value, { zoomControl: true, attributionControl: false }).setView(
        [parsedLat.value, parsedLng.value],
        15,
    );
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png").addTo(mapInstance.value);
    markerInstance.value = L.marker([parsedLat.value, parsedLng.value], { icon: defaultIcon })
        .addTo(mapInstance.value)
        .bindPopup(props.form.alamat_data || "Lokasi");
};

const updateMarker = () => {
    if (!mapInstance.value || !hasCoords.value) return;
    const latlng = [parsedLat.value, parsedLng.value];
    if (markerInstance.value) markerInstance.value.setLatLng(latlng);
    else {
        markerInstance.value = L.marker(latlng, { icon: defaultIcon }).addTo(mapInstance.value);
    }
    mapInstance.value.setView(latlng, mapInstance.value.getZoom());
};

watch(hasCoords, (val) => {
    if (val) {
        if (!mapInstance.value) setTimeout(initMap, 50);
        else updateMarker();
    }
});

watch([parsedLat, parsedLng], () => {
    if (hasCoords.value && mapInstance.value) updateMarker();
});

onMounted(() => {
    if (hasCoords.value) setTimeout(initMap, 50);
});

onBeforeUnmount(() => {
    if (mapInstance.value) {
        mapInstance.value.remove();
        mapInstance.value = null;
    }
});

const mapsUrl = computed(() =>
    hasCoords.value
        ? `https://www.google.com/maps?q=${parsedLat.value},${parsedLng.value}`
        : `https://www.google.com/maps/search/${encodeURIComponent(props.form.alamat_data || "")}`,
);
</script>

<template>
    <div class="space-y-8 p-6 sm:p-8">
        <UiSectionHeader title="Lokasi & Koordinat" subtitle="Detail alamat, wilayah administratif, dan titik koordinat GPS properti." icon="pi pi-map-marker" />

        <UiField id="alamat_data" label="Alamat Lengkap" :required="true" :error="form.errors.alamat_data">
            <Textarea
                v-model="form.alamat_data"
                inputId="alamat_data"
                auto-resize
                rows="2"
                placeholder="mis. Jalan Merdeka No. 10, RT 01/RW 02"
                class="w-full rounded-xl bg-slate-50/50 border-slate-200"
            />
        </UiField>

        <div class="grid gap-6 md:grid-cols-2">
            <UiField id="province_id" label="Provinsi" :required="true" :error="form.errors.province_id">
                <Select
                    v-model="form.province_id"
                    :options="options.provinces ?? []"
                    option-label="label"
                    option-value="value"
                    placeholder="Pilih provinsi"
                    class="w-full rounded-xl bg-slate-50/50"
                    inputId="province_id"
                    filter
                />
            </UiField>

            <UiField id="regency_id" label="Kabupaten/Kota" :required="true" :error="form.errors.regency_id">
                <Select
                    v-model="form.regency_id"
                    :options="regencyOptions ?? []"
                    option-label="label"
                    option-value="value"
                    placeholder="Pilih kabupaten/kota"
                    class="w-full rounded-xl bg-slate-50/50"
                    inputId="regency_id"
                    :disabled="!form.province_id"
                    filter
                />
            </UiField>

            <UiField id="district_id" label="Kecamatan" :required="true" :error="form.errors.district_id">
                <Select
                    v-model="form.district_id"
                    :options="districtOptions ?? []"
                    option-label="label"
                    option-value="value"
                    placeholder="Pilih kecamatan"
                    class="w-full rounded-xl bg-slate-50/50"
                    inputId="district_id"
                    :disabled="!form.regency_id"
                    filter
                />
            </UiField>

            <UiField id="village_id" label="Desa/Kelurahan" :required="true" :error="form.errors.village_id">
                <Select
                    v-model="form.village_id"
                    :options="villageOptions ?? []"
                    option-label="label"
                    option-value="value"
                    placeholder="Pilih desa/kelurahan"
                    class="w-full rounded-xl bg-slate-50/50"
                    inputId="village_id"
                    :disabled="!form.district_id"
                    filter
                />
            </UiField>
        </div>

        <UiSurface variant="inset" class="p-6 bg-slate-900 text-white rounded-2xl overflow-hidden shadow-xl shadow-slate-200">
            <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
                <div>
                    <p class="text-lg font-bold">Titik Koordinat GPS</p>
                    <p class="text-xs text-slate-400 mt-1">Masukkan koordinat secara manual atau cari melalui Google Maps.</p>
                </div>

                <a
                    :href="mapsUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-700 bg-slate-800 px-4 text-xs font-bold text-white hover:bg-slate-700 transition shadow-sm"
                >
                    <i class="pi pi-external-link text-[12px]" aria-hidden="true" />
                    {{ hasCoords ? "Buka di Google Maps" : "Cari di Maps" }}
                </a>
            </div>

            <div class="grid gap-6 sm:grid-cols-2 mb-6">
                <UiField id="latitude" label="Latitude" :required="true" :error="form.errors.latitude">
                    <InputText v-model="form.latitude" id="latitude" placeholder="mis. -6.200000" class="w-full rounded-xl bg-slate-800 border-slate-700 text-white placeholder:text-slate-500 font-mono" />
                </UiField>
                <UiField id="longitude" label="Longitude" :required="true" :error="form.errors.longitude">
                    <InputText
                        v-model="form.longitude"
                        id="longitude"
                        placeholder="mis. 106.816666"
                        class="w-full rounded-xl bg-slate-800 border-slate-700 text-white placeholder:text-slate-500 font-mono"
                    />
                </UiField>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-700 bg-slate-800">
                <div class="flex items-center justify-between border-b border-slate-700 bg-slate-800/50 px-4 py-2">
                    <span class="text-xs font-bold text-slate-300">Live Map Preview</span>
                    <span class="font-mono text-[10px] text-slate-500 uppercase tracking-widest">
                        {{ hasCoords ? `${parsedLat}, ${parsedLng}` : "Empty Coords" }}
                    </span>
                </div>
                <div v-if="hasCoords" ref="mapContainer" class="mini-map w-full opacity-90 grayscale hover:grayscale-0 transition-all duration-500" />
                <div v-else class="mini-map flex flex-col items-center justify-center p-8 text-center bg-slate-800/20">
                    <i class="pi pi-map text-3xl text-slate-700 mb-2" />
                    <p class="text-[11px] text-slate-500 font-medium">Input koordinat untuk mengaktifkan preview peta interaktif.</p>
                </div>
            </div>
        </UiSurface>

        <div class="flex justify-between border-t border-slate-100 pt-6">
            <Button label="Kembali" icon="pi pi-arrow-left" severity="secondary" outlined class="rounded-xl px-6" @click="emit('prev')" />
            <Button
                label="Lanjut ke Properti"
                icon="pi pi-arrow-right"
                icon-pos="right"
                severity="primary"
                class="rounded-xl px-6"
                @click="emit('next')"
            />
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

.mini-map {
    height: 220px;
}
</style>
