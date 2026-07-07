import { onBeforeUnmount, onMounted, ref, unref, watch } from "vue";
import L from "leaflet";
import "leaflet.markercluster";

/**
 * Manage a Leaflet map with clustered markers and automatic rerender/cleanup.
 *
 * @param {{
 *   markers: import("vue").Ref|Function|Array,
 *   buildMarker: Function,
 *   defaultCenter?: [number, number],
 *   defaultZoom?: number,
 *   fitBoundsOptions?: Object,
 *   clusterOptions?: Object,
 *   layers?: Object|Function,
 *   osmFirst?: boolean,
 *   onMapReady?: Function,
 *   afterRender?: Function,
 * }} options
 * @returns {{ mapContainer: import("vue").Ref, mapInstance: import("vue").Ref, markerLayer: import("vue").Ref, ensureMap: Function, renderMarkers: Function, destroyMap: Function }}
 */
export function useClusteredLeafletMap(options) {
    const mapContainer = ref(null);
    const mapInstance = ref(null);
    const markerLayer = ref(null);

    const getMarkers = () => {
        const value = typeof options.markers === "function" ? options.markers() : unref(options.markers);
        return Array.isArray(value) ? value : [];
    };

    const defaultCenter = options.defaultCenter ?? [-2.5489, 118.0149];
    const defaultZoom = options.defaultZoom ?? 5;
    const fitBoundsOptions = options.fitBoundsOptions ?? { padding: [20, 20], maxZoom: 15 };

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
        const defaultLayers = options.osmFirst
            ? { "OSM Basic": osm, Satellite: satellite }
            : { Satellite: satellite, "OSM Basic": osm };
        const layers = typeof options.layers === "function"
            ? options.layers({ osm, satellite, L })
            : (options.layers ?? defaultLayers);
        L.control.layers(layers).addTo(mapInstance.value);

        markerLayer.value = L.markerClusterGroup({
            chunkedLoading: true,
            maxClusterRadius: 60,
            spiderfyOnMaxZoom: true,
            showCoverageOnHover: false,
            ...(options.clusterOptions ?? {}),
        }).addTo(mapInstance.value);

        mapInstance.value.setView(defaultCenter, defaultZoom);
        options.onMapReady?.({ map: mapInstance.value, markerLayer: markerLayer.value, L });
    };

    const renderMarkers = () => {
        ensureMap();
        if (!mapInstance.value || !markerLayer.value) return;

        markerLayer.value.clearLayers();
        const markers = getMarkers();

        if (markers.length === 0) {
            mapInstance.value.setView(defaultCenter, defaultZoom);
            options.afterRender?.({ map: mapInstance.value, markerLayer: markerLayer.value, bounds: [], empty: true, L });
            return;
        }

        const bounds = [];
        markers.forEach((item) => {
            const marker = options.buildMarker?.(item, { map: mapInstance.value, L });
            if (!marker) return;

            markerLayer.value.addLayer(marker);
            const latLng = marker.getLatLng?.();
            if (latLng) bounds.push([latLng.lat, latLng.lng]);
        });

        if (bounds.length > 0) {
            mapInstance.value.fitBounds(bounds, fitBoundsOptions);
        } else {
            mapInstance.value.setView(defaultCenter, defaultZoom);
        }

        options.afterRender?.({ map: mapInstance.value, markerLayer: markerLayer.value, bounds, empty: bounds.length === 0, L });
    };

    const destroyMap = () => {
        if (mapInstance.value) {
            mapInstance.value.remove();
            mapInstance.value = null;
        }
        markerLayer.value = null;
    };

    onMounted(renderMarkers);

    watch(() => getMarkers(), renderMarkers, { deep: true });

    onBeforeUnmount(destroyMap);

    return {
        mapContainer,
        mapInstance,
        markerLayer,
        ensureMap,
        renderMarkers,
        destroyMap,
    };
}
