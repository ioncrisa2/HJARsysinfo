<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import Textarea from "primevue/textarea";
import L from "leaflet";
import markerIcon from "leaflet/dist/images/marker-icon.png";
import markerIcon2x from "leaflet/dist/images/marker-icon-2x.png";
import markerShadow from "leaflet/dist/images/marker-shadow.png";
import "leaflet/dist/leaflet.css";

const props = defineProps({
    form:            { type: Object, required: true },
    options:         { type: Object, default: () => ({}) },
    regencyOptions:  { type: Array,  default: () => [] },
    districtOptions: { type: Array,  default: () => [] },
    villageOptions:  { type: Array,  default: () => [] },
});

const emit = defineEmits(["prev", "next"]);

// ── Mini map ────────────────────────────────────────────────
const mapContainer  = ref(null);
const mapInstance   = ref(null);
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
    iconUrl:       markerIcon,
    shadowUrl:     markerShadow,
    iconSize:      [25, 41],
    iconAnchor:    [12, 41],
    popupAnchor:   [1, -34],
    shadowSize:    [41, 41],
});

const initMap = () => {
    if (!mapContainer.value || mapInstance.value) return;
    mapInstance.value = L.map(mapContainer.value, { zoomControl: true, attributionControl: false })
        .setView([parsedLat.value, parsedLng.value], 15);
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png").addTo(mapInstance.value);
    markerInstance.value = L.marker([parsedLat.value, parsedLng.value], { icon: defaultIcon })
        .addTo(mapInstance.value)
        .bindPopup(props.form.alamat_data || "Lokasi")
        .openPopup();
};

const updateMarker = () => {
    if (!mapInstance.value || !hasCoords.value) return;
    const latlng = [parsedLat.value, parsedLng.value];
    if (markerInstance.value) {
        markerInstance.value.setLatLng(latlng);
    } else {
        markerInstance.value = L.marker(latlng, { icon: defaultIcon })
            .addTo(mapInstance.value)
            .bindPopup(props.form.alamat_data || "Lokasi");
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

onMounted(() => { if (hasCoords.value) setTimeout(initMap, 50); });
onBeforeUnmount(() => { if (mapInstance.value) { mapInstance.value.remove(); mapInstance.value = null; } });

// Google Maps link
const mapsUrl = computed(() =>
    hasCoords.value
        ? `https://www.google.com/maps?q=${parsedLat.value},${parsedLng.value}`
        : `https://www.google.com/maps/search/${encodeURIComponent(props.form.alamat_data || "")}`
);
</script>

<template>
    <div class="space-y-6 p-5">

        <!-- ── Alamat ──────────────────────────────────── -->
        <section>
            <div class="mb-4 flex items-center gap-2.5">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-100">
                    <i class="pi pi-home text-amber-600" style="font-size: 12px" />
                </div>
                <h2 class="text-sm font-bold text-slate-700">Alamat Properti</h2>
            </div>

            <div class="space-y-1.5">
                <label class="text-xs font-semibold text-slate-500">
                    Alamat Lengkap <span class="text-red-400">*</span>
                </label>
                <Textarea
                    v-model="form.alamat_data"
                    auto-resize
                    rows="2"
                    placeholder="mis. Jalan Merdeka No. 10, RT 01/RW 02"
                    class="w-full"
                />
                <p v-if="form.errors.alamat_data" class="flex items-center gap-1 text-xs text-red-500">
                    <i class="pi pi-exclamation-circle text-[10px]" />
                    {{ form.errors.alamat_data }}
                </p>
            </div>
        </section>

        <hr class="border-slate-100" />

        <!-- ── Wilayah Administratif ───────────────────── -->
        <section>
            <div class="mb-4 flex items-center gap-2.5">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-100">
                    <i class="pi pi-map text-amber-600" style="font-size: 12px" />
                </div>
                <h2 class="text-sm font-bold text-slate-700">Wilayah Administratif</h2>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-500">
                        Provinsi <span class="text-red-400">*</span>
                    </label>
                    <Select
                        v-model="form.province_id"
                        :options="options.provinces ?? []"
                        option-label="label"
                        option-value="value"
                        filter
                        placeholder="Cari provinsi..."
                        class="w-full"
                    />
                    <p v-if="form.errors.province_id" class="flex items-center gap-1 text-xs text-red-500">
                        <i class="pi pi-exclamation-circle text-[10px]" />{{ form.errors.province_id }}
                    </p>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-500">
                        Kabupaten / Kota <span class="text-red-400">*</span>
                    </label>
                    <Select
                        v-model="form.regency_id"
                        :options="regencyOptions"
                        option-label="label"
                        option-value="value"
                        filter
                        placeholder="Pilih provinsi dulu"
                        class="w-full"
                        :disabled="!form.province_id"
                    />
                    <p v-if="!form.province_id" class="text-[11px] text-slate-400">
                        <i class="pi pi-info-circle text-[10px]" /> Pilih provinsi terlebih dahulu.
                    </p>
                    <p v-if="form.errors.regency_id" class="flex items-center gap-1 text-xs text-red-500">
                        <i class="pi pi-exclamation-circle text-[10px]" />{{ form.errors.regency_id }}
                    </p>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-500">
                        Kecamatan <span class="text-red-400">*</span>
                    </label>
                    <Select
                        v-model="form.district_id"
                        :options="districtOptions"
                        option-label="label"
                        option-value="value"
                        filter
                        placeholder="Pilih kab/kota dulu"
                        class="w-full"
                        :disabled="!form.regency_id"
                    />
                    <p v-if="!form.regency_id" class="text-[11px] text-slate-400">
                        <i class="pi pi-info-circle text-[10px]" /> Pilih kabupaten/kota terlebih dahulu.
                    </p>
                    <p v-if="form.errors.district_id" class="flex items-center gap-1 text-xs text-red-500">
                        <i class="pi pi-exclamation-circle text-[10px]" />{{ form.errors.district_id }}
                    </p>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-500">
                        Desa / Kelurahan <span class="text-red-400">*</span>
                    </label>
                    <Select
                        v-model="form.village_id"
                        :options="villageOptions"
                        option-label="label"
                        option-value="value"
                        filter
                        placeholder="Pilih kecamatan dulu"
                        class="w-full"
                        :disabled="!form.district_id"
                    />
                    <p v-if="!form.district_id" class="text-[11px] text-slate-400">
                        <i class="pi pi-info-circle text-[10px]" /> Pilih kecamatan terlebih dahulu.
                    </p>
                    <p v-if="form.errors.village_id" class="flex items-center gap-1 text-xs text-red-500">
                        <i class="pi pi-exclamation-circle text-[10px]" />{{ form.errors.village_id }}
                    </p>
                </div>
            </div>
        </section>

        <hr class="border-slate-100" />

        <!-- ── Koordinat GPS ───────────────────────────── -->
        <section>
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-100">
                        <i class="pi pi-map-marker text-amber-600" style="font-size: 12px" />
                    </div>
                    <h2 class="text-sm font-bold text-slate-700">Koordinat GPS</h2>
                </div>
                <a
                    :href="mapsUrl"
                    target="_blank"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-600"
                >
                    <i class="pi pi-external-link text-[10px]" />
                    {{ hasCoords ? "Lihat di Maps" : "Cari di Google Maps" }}
                </a>
            </div>

            <!-- Info box -->
            <div class="mb-4 flex items-start gap-2.5 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-xs text-blue-700">
                <i class="pi pi-info-circle mt-0.5 shrink-0 text-blue-400" style="font-size: 13px" />
                <div>
                    Masukkan koordinat GPS lokasi properti. Buka Google Maps, klik kanan pada titik lokasi,
                    lalu salin koordinat yang muncul. Format: <strong>-6.200000, 106.816666</strong>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-500">
                        Latitude <span class="text-red-400">*</span>
                    </label>
                    <InputText v-model="form.latitude" placeholder="mis. -6.200000" class="w-full" />
                    <p v-if="form.errors.latitude" class="flex items-center gap-1 text-xs text-red-500">
                        <i class="pi pi-exclamation-circle text-[10px]" />{{ form.errors.latitude }}
                    </p>
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-500">
                        Longitude <span class="text-red-400">*</span>
                    </label>
                    <InputText v-model="form.longitude" placeholder="mis. 106.816666" class="w-full" />
                    <p v-if="form.errors.longitude" class="flex items-center gap-1 text-xs text-red-500">
                        <i class="pi pi-exclamation-circle text-[10px]" />{{ form.errors.longitude }}
                    </p>
                </div>
            </div>

            <!-- Mini map preview -->
            <Transition name="map-reveal">
                <div
                    v-if="hasCoords"
                    class="mt-4 overflow-hidden rounded-xl border border-slate-200 shadow-sm"
                >
                    <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/70 px-3 py-2">
                        <span class="flex items-center gap-1.5 text-xs font-semibold text-slate-600">
                            <i class="pi pi-map-marker text-[11px] text-emerald-500" />
                            Preview Lokasi
                        </span>
                        <span class="text-[10px] text-slate-400">{{ parsedLat }}, {{ parsedLng }}</span>
                    </div>
                    <div ref="mapContainer" class="mini-map w-full" />
                </div>
            </Transition>

            <!-- Placeholder saat kosong -->
            <div
                v-if="!hasCoords"
                class="mt-4 flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-slate-200 bg-slate-50 py-8 text-center"
            >
                <i class="pi pi-map text-2xl text-slate-300" />
                <p class="text-xs text-slate-400">Preview peta akan muncul setelah koordinat diisi.</p>
            </div>
        </section>

        <!-- Nav -->
        <div class="flex justify-between border-t border-slate-100 pt-4">
            <Button label="Kembali" icon="pi pi-arrow-left" severity="secondary" outlined @click="emit('prev')" />
            <Button
                label="Lanjut ke Properti"
                icon="pi pi-arrow-right"
                icon-pos="right"
                severity="secondary"
                outlined
                @click="emit('next')"
            />
        </div>
    </div>
</template>

<style scoped>
:deep(.leaflet-pane),
:deep(.leaflet-top),
:deep(.leaflet-bottom),
:deep(.leaflet-control) { z-index: 10 !important; }

.mini-map { height: 220px; }

.map-reveal-enter-active { transition: all 0.3s ease; }
.map-reveal-enter-from   { opacity: 0; transform: translateY(8px); }
</style>
