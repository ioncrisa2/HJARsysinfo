<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4 w-full">
                <h2 class="text-lg font-bold">Peta Sebaran Data</h2>

                <div class="flex items-center gap-2">
                    <div class="w-28">
                        <x-filament::input.wrapper>
                            <x-filament::input type="number" step="any" wire:model="latInput" placeholder="Latitude" />
                        </x-filament::input.wrapper>
                    </div>
                    <div class="w-28">
                        <x-filament::input.wrapper>
                            <x-filament::input type="number" step="any" wire:model="lngInput" placeholder="Longitude" />
                        </x-filament::input.wrapper>
                    </div>
                    <x-filament::button wire:click="searchLocation" icon="heroicon-m-magnifying-glass">
                        Cari
                    </x-filament::button>
                </div>
            </div>
        </x-slot>

        @once
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" />
            <script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>

            <style>
                .leaflet-control-layers {
                    background: white !important;
                    border-radius: 8px !important;
                    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
                    border: none !important;
                    padding: 10px !important;
                }

                .dark .leaflet-control-layers {
                    background: #1f2937 !important;
                    color: #e5e7eb !important;
                }

                .dark .leaflet-control-layers-base span {
                    color: #9ca3af !important;
                }
            </style>
        @endonce

        <div
            wire:ignore
            x-data="{
                map: null,
                layerGroup: null,
                userMarkerLayer: null,
                center: @js($mapCenter),
                zoom: @js($mapZoom),
                markers: @js($this->getAllMarkers()),
                mouseLat: null,
                mouseLng: null,

                init() {
                    if (typeof L === 'undefined') {
                        setTimeout(() => this.init(), 120);
                        return;
                    }

                    if (this.map) return;

                    const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '© OpenStreetMap'
                    });

                    const googleSat = L.tileLayer('https://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}', {
                        maxZoom: 20,
                        subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
                    });

                    this.map = L.map(this.$refs.mapContainer, {
                        center: this.center,
                        zoom: this.zoom,
                        layers: [osm],
                        zoomControl: false
                    });

                    const baseMaps = {
                        'OpenStreetMap': osm,
                        'Satelit (Google)': googleSat
                    };

                    L.control.layers(baseMaps).addTo(this.map);
                    L.control.zoom({ position: 'bottomright' }).addTo(this.map);

                    this.layerGroup = L.layerGroup().addTo(this.map);
                    this.userMarkerLayer = L.layerGroup().addTo(this.map);
                    this.renderMarkers(this.markers);

                    const selected = this.markers.find((m) => (m.icon || '').includes('red.png'));
                    if (selected) this.setUserMarker(selected.lat, selected.lng);

                    this.map.on('mousemove', (e) => {
                        this.mouseLat = e.latlng.lat.toFixed(5);
                        this.mouseLng = e.latlng.lng.toFixed(5);
                    });

                    this.map.on('click', (e) => {
                        const lat = Number(e.latlng.lat.toFixed(6));
                        const lng = Number(e.latlng.lng.toFixed(6));

                        $wire.set('latInput', lat);
                        $wire.set('lngInput', lng);

                        this.setUserMarker(lat, lng);
                        this.map.flyTo([lat, lng], 15);
                    });

                    setTimeout(() => this.map.invalidateSize(), 200);
                },

                renderMarkers(dataMarkers) {
                    if (!this.layerGroup) return;

                    this.layerGroup.clearLayers();

                    dataMarkers.forEach((point) => {
                        const iconUrl = point.icon || 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png';

                        if (iconUrl.includes('red.png')) return;

                        const icon = L.icon({
                            iconUrl,
                            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                            iconSize: [25, 41],
                            iconAnchor: [12, 41],
                            popupAnchor: [1, -34],
                            shadowSize: [41, 41],
                        });

                        L.marker([point.lat, point.lng], { icon })
                            .bindPopup(point.popup ?? '')
                            .addTo(this.layerGroup);
                    });
                },

                setUserMarker(lat, lng) {
                    if (!this.userMarkerLayer) return;

                    this.userMarkerLayer.clearLayers();

                    const redIcon = L.icon({
                        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                        shadowSize: [41, 41],
                    });

                    L.marker([lat, lng], { icon: redIcon })
                        .bindPopup(`<b>Lokasi Dipilih</b><br>Lat: ${lat}<br>Lng: ${lng}`)
                        .addTo(this.userMarkerLayer)
                        .openPopup();
                },

                updateMap(newCenter, newZoom, newMarkers) {
                    if (!this.map) return;

                    this.map.setView(newCenter, newZoom);
                    this.renderMarkers(newMarkers);

                    const selected = newMarkers.find((m) => (m.icon || '').includes('red.png'));
                    if (selected) this.setUserMarker(selected.lat, selected.lng);
                }
            }"
            x-on:map-updated.window="$wire.getAllMarkers().then((markers) => updateMap($wire.mapCenter, $wire.mapZoom, markers))"
            class="relative"
        >
            <div
                x-ref="mapContainer"
                style="height: 500px; width: 100%; z-index: 1;"
                class="rounded-lg bg-gray-100 dark:bg-gray-800"
            ></div>

            <div
                class="absolute bottom-1 left-4 z-50 bg-white/90 dark:bg-gray-900/90 backdrop-blur-sm border border-gray-300 dark:border-gray-700 rounded-md shadow-lg px-3 py-2 text-xs font-mono text-gray-700 dark:text-gray-200 pointer-events-none"
                x-show="mouseLat !== null"
            >
                <div class="flex gap-3">
                    <span>LAT: <span x-text="mouseLat" class="font-bold text-primary-600"></span></span>
                    <span>LNG: <span x-text="mouseLng" class="font-bold text-primary-600"></span></span>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
