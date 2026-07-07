import { nextTick, onBeforeUnmount, onMounted, ref, unref, watch } from "vue";
import L from "leaflet";
import markerIcon from "leaflet/dist/images/marker-icon.png";
import markerIcon2x from "leaflet/dist/images/marker-icon-2x.png";
import markerShadow from "leaflet/dist/images/marker-shadow.png";

/**
 * Manage a Leaflet map with one marker, coordinate updates, and cleanup.
 *
 * @param {{
 *   lat: import("vue").Ref|Function,
 *   lng: import("vue").Ref|Function,
 *   hasCoordinates: import("vue").Ref|Function,
 *   popupText?: import("vue").Ref|Function|string,
 *   zoom?: number,
 *   attributionControl?: boolean,
 *   tileUrl?: string,
 *   tileOptions?: Object,
 *   invalidateDelay?: number,
 *   initDelay?: number,
 * }} options
 * @returns {{ mapContainer: import("vue").Ref, mapInstance: import("vue").Ref, markerInstance: import("vue").Ref, initMap: Function, updateMarker: Function, destroyMap: Function }}
 */
export function useSingleMarkerLeafletMap(options) {
    const mapContainer = ref(null);
    const mapInstance = ref(null);
    const markerInstance = ref(null);

    const getValue = (value) => (typeof value === "function" ? value() : unref(value));
    const getLat = () => getValue(options.lat);
    const getLng = () => getValue(options.lng);
    const hasCoordinates = () => Boolean(getValue(options.hasCoordinates));
    const getPopupText = () => getValue(options.popupText) || "Lokasi";

    const zoom = options.zoom ?? 15;
    const tileUrl = options.tileUrl ?? "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png";
    const invalidateDelay = options.invalidateDelay ?? 80;
    const initDelay = options.initDelay ?? 50;

    const defaultIcon = L.icon({
        iconRetinaUrl: markerIcon2x,
        iconUrl: markerIcon,
        shadowUrl: markerShadow,
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41],
    });

    const invalidateSize = () => {
        setTimeout(() => mapInstance.value?.invalidateSize(), invalidateDelay);
    };

    const initMap = async () => {
        if (!mapContainer.value || mapInstance.value || !hasCoordinates()) return;

        await nextTick();

        mapInstance.value = L.map(mapContainer.value, {
            zoomControl: true,
            attributionControl: options.attributionControl ?? false,
        }).setView([getLat(), getLng()], zoom);

        L.tileLayer(tileUrl, options.tileOptions ?? {}).addTo(mapInstance.value);
        markerInstance.value = L.marker([getLat(), getLng()], { icon: defaultIcon })
            .addTo(mapInstance.value)
            .bindPopup(getPopupText());
        invalidateSize();
    };

    const updateMarker = () => {
        if (!mapInstance.value || !hasCoordinates()) return;

        const latlng = [getLat(), getLng()];
        if (markerInstance.value) {
            markerInstance.value.setLatLng(latlng).bindPopup(getPopupText());
        } else {
            markerInstance.value = L.marker(latlng, { icon: defaultIcon })
                .addTo(mapInstance.value)
                .bindPopup(getPopupText());
        }

        mapInstance.value.setView(latlng, mapInstance.value.getZoom());
        mapInstance.value.invalidateSize();
    };

    const destroyMap = () => {
        if (mapInstance.value) {
            mapInstance.value.remove();
            mapInstance.value = null;
        }
        markerInstance.value = null;
    };

    const scheduleInit = () => setTimeout(initMap, initDelay);

    watch(() => hasCoordinates(), async (value) => {
        if (value) {
            await nextTick();
            if (!mapInstance.value) scheduleInit();
            else updateMarker();
        } else {
            destroyMap();
        }
    });

    watch([getLat, getLng, getPopupText], () => {
        if (hasCoordinates() && mapInstance.value) updateMarker();
    });

    onMounted(() => {
        if (hasCoordinates()) scheduleInit();
    });

    onBeforeUnmount(destroyMap);

    return {
        mapContainer,
        mapInstance,
        markerInstance,
        initMap,
        updateMarker,
        destroyMap,
    };
}
