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

        {{-- 2. LOAD LIBRARY --}}
        @once
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
            <style>
                .leaflet-control-layers {
                    background: white !important;
                    border-radius: 8px !important;
                    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
                    border: none !important;
                    padding: 10px !important;
                    font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                }

                .layer-control-title {
                    font-weight: 700;
                    font-size: 12px;
                    color: #374151; /* Gray-700 */
                    margin-bottom: 8px;
                    padding-bottom: 6px;
                    border-bottom: 1px solid #e5e7eb;
                    text-transform: uppercase;
                    letter-spacing: 0.05em;
                }

                .leaflet-control-layers-base label {
                    display: flex !important;
                    align-items: center !important;
                    margin-bottom: 6px !important;
                    cursor: pointer;
                    transition: all 0.2s;
                }

                .leaflet-control-layers-base label:hover {
                    background-color: #f3f4f6;
                    border-radius: 4px;
                }

                .leaflet-control-layers-base span {
                    font-size: 13px !important;
                    color: #4b5563 !important; /* Gray-600 */
                    margin-left: 4px;
                }

                .dark .leaflet-control-layers {
                    background: #1f2937 !important; /* Gray-800 */
                    color: #e5e7eb !important;
                }
                .dark .layer-control-title {
                    color: #d1d5db;
                    border-color: #374151;
                }
                .dark .leaflet-control-layers-base span {
                    color: #9ca3af !important;
                }
                .dark .leaflet-control-layers-base label:hover {
                    background-color: #374151;
                }
            </style>
        @endonce

        {{-- 3. MAP CONTAINER --}}
        <div
            wire:ignore
            x-data="{
                map: null,
                layerGroup: null,
                userMarkerLayer: null,
                center: @js($mapCenter),
                zoom: @js($mapZoom),
                markers: @js($this->getAllMarkers()),
                mouseLat: 0,
                mouseLng: 0,

                init() {
                    if (typeof L === 'undefined') {
                        setTimeout(() => this.init(), 100);
                        return;
                    }
                    if (this.map) return;

                    const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '© OpenStreetMap'
                    });

                    const googleSat = L.tileLayer('http://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}',{
                        maxZoom: 20,
                        subdomains:['mt0','mt1','mt2','mt3']
                    });

                    this.map = L.map(this.$refs.mapContainer, {
                        center: this.center,
                        zoom: this.zoom,
                        layers: [googleSat],
                        zoomControl: false
                    });

                    const baseMaps = {
                        'Satelit (Google)': googleSat,
                        'OpenStreetMap': osm
                    };

                    const legend = L.control({ position: 'topright' });
                    legend.onAdd = function (map) {
                        const div = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
                        div.style.backgroundColor = 'white';
                        div.style.padding = '10px';
                        div.style.borderRadius = '4px';
                        div.style.boxShadow = '0 1px 5px rgba(0,0,0,0.4)';
                        div.style.minWidth = '130px';

                        div.innerHTML = `
                            <div style='font-weight: bold; margin-bottom: 8px; font-size: 12px; color: #333; border-bottom: 1px solid #eee; padding-bottom: 5px;'>
                                Keterangan
                            </div>
                            <div style='display: flex; align-items: center; margin-bottom: 5px;'>
                                <img src='https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png' style='height: 16px; margin-right: 8px;'>
                                <span style='font-size: 11px; color: #333;'>Data Transaksi</span>
                            </div>
                            <div style='display: flex; align-items: center; margin-bottom: 5px;'>
                                <img src='https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-orange.png' style='height: 16px; margin-right: 8px;'>
                                <span style='font-size: 11px; color: #333;'>Data Penawaran</span>
                            </div>
                            <div style='display: flex; align-items: center; margin-bottom: 5px;'>
                                <img src='https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png' style='height: 16px; margin-right: 8px;'>
                                <span style='font-size: 11px; color: #333;'>Lainnya</span>
                            </div>
                            <div style='display: flex; align-items: center; margin-bottom: 5px;'>
                                <img src='https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png' style='height: 16px; margin-right: 8px;'>
                                <span style='font-size: 11px; color: #333; font-weight: 600;'>Pencarian Lokasi</span>
                            </div>
                        `;
                        return div;
                    };
                    legend.addTo(this.map);

                    L.control.layers(baseMaps).addTo(this.map);
                    L.control.zoom({position: 'bottomright'}).addTo(this.map);

                    this.layerGroup = L.layerGroup().addTo(this.map);
                    this.userMarkerLayer = L.layerGroup().addTo(this.map);
                    this.renderMarkers(this.markers);

                    this.map.on('mousemove', (e) => {
                        this.mouseLat = e.latlng.lat.toFixed(5);
                        this.mouseLng = e.latlng.lng.toFixed(5);
                    });

                    this.map.on('click', (e) => {
                        const lat = e.latlng.lat.toFixed(6);
                        const lng = e.latlng.lng.toFixed(6);

                        $wire.set('latInput', lat);
                        $wire.set('lngInput', lng);

                        this.setUserMarker(lat, lng);

                        this.map.flyTo([lat, lng], 10);
                    });

                    setTimeout(() => { this.map.invalidateSize(); }, 200);
                },

                // Render Data DB (Biru/Abu)
                renderMarkers(dataMarkers) {

                    this.layerGroup.clearLayers();
                    dataMarkers.forEach(point => {
                        if(point.icon.includes('red.png')) return;

                        const myIcon = L.icon({
                            iconUrl: point.icon,
                            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                            iconSize: [25, 41],
                            iconAnchor: [12, 41],
                            popupAnchor: [1, -34],
                            shadowSize: [41, 41]
                        });
                        L.marker([point.lat, point.lng], {icon: myIcon})
                            .bindPopup(point.popup)
                            .addTo(this.layerGroup);
                    });
                },

                setUserMarker(lat, lng) {
                    this.userMarkerLayer.clearLayers();

                    const redIcon = L.icon({
                        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                        shadowSize: [41, 41]
                    });

                    L.marker([lat, lng], {icon: redIcon})
                        .bindPopup(`<b>Lokasi Dipilih</b><br>Lat: ${lat}<br>Lng: ${lng}`)
                        .addTo(this.userMarkerLayer)
                        .openPopup();

                    this.drawRadius(lat,lng)
                },

                updateMap(newCenter, newZoom, newMarkers) {
                    if (!this.map) return;
                    this.map.setView(newCenter, newZoom);
                    this.renderMarkers(newMarkers);

                    const userPin = newMarkers.find(m => m.icon.includes('red.png'));
                    if (userPin) {
                        this.setUserMarker(userPin.lat, userPin.lng);
                    }
                }
            }"
            x-on:map-updated.window="updateMap($wire.mapCenter, $wire.mapZoom, await $wire.getAllMarkers())"
            class="relative group"
        >
            <div
                x-ref="mapContainer"
                style="height: 500px; width: 100%; z-index: 1;"
                class="rounded-lg bg-gray-100 dark:bg-gray-800"
            ></div>

            {{-- KOTAK POSISI MOUSE --}}
            <div
                class="absolute bottom-1 left-4 z-1000 bg-white/90 dark:bg-gray-900/90 backdrop-blur-sm border border-gray-300 dark:border-gray-700 rounded-md shadow-lg px-3 py-2 text-xs font-mono text-gray-700 dark:text-gray-200 pointer-events-none transition-opacity duration-200"
                x-show="mouseLat != 0"
                x-transition
            >
                <div class="flex gap-3">
                    <span>LAT: <span x-text="mouseLat" class="font-bold text-primary-600"></span></span>
                    <span>LNG: <span x-text="mouseLng" class="font-bold text-primary-600"></span></span>
                </div>
            </div>

        </div>
    </x-filament::section>
</x-filament-widgets::widget>
