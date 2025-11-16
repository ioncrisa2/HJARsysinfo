document.addEventListener('alpine:init', () => {
    Alpine.data('customMapWidget', (config) => ({
        map: null,
        layerGroup: null,
        userMarkerLayer: null,

        // Ambil konfigurasi dari parameter
        center: config.center,
        zoom: config.zoom,
        markers: config.markers,

        mouseLat: 0,
        mouseLng: 0,

        init() {
            // Safety check jika Leaflet belum load
            if (typeof L === 'undefined') {
                setTimeout(() => this.init(), 100);
                return;
            }

            // Safety check double init
            if (this.map) return;

            // 1. Definisi Layer
            const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap'
            });

            const googleSat = L.tileLayer('http://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}',{
                maxZoom: 20,
                subdomains:['mt0','mt1','mt2','mt3']
            });

            // 2. Setup Map
            // Perhatikan: kita pakai this.$refs.mapContainer
            this.map = L.map(this.$refs.mapContainer, {
                center: this.center,
                zoom: this.zoom,
                layers: [googleSat],
                zoomControl: false
            });

            // 3. Controls
            const baseMaps = {
                'Satelit (Google)': googleSat,
                'OpenStreetMap': osm
            };
            L.control.layers(baseMaps).addTo(this.map);
            L.control.zoom({position: 'topleft'}).addTo(this.map);

            // 4. Init Layers
            this.layerGroup = L.layerGroup().addTo(this.map);
            this.userMarkerLayer = L.layerGroup().addTo(this.map);

            this.renderMarkers(this.markers);

            // 5. Event Mouse Move
            this.map.on('mousemove', (e) => {
                this.mouseLat = e.latlng.lat.toFixed(5);
                this.mouseLng = e.latlng.lng.toFixed(5);
            });

            // 6. Click to Pick + Zoom
            this.map.on('click', (e) => {
                const lat = e.latlng.lat.toFixed(6);
                const lng = e.latlng.lng.toFixed(6);

                // Panggil Livewire set
                this.$wire.set('latInput', lat);
                this.$wire.set('lngInput', lng);

                this.setUserMarker(lat, lng);
                this.map.flyTo([lat, lng], 10);
            });

            setTimeout(() => { this.map.invalidateSize(); }, 200);
        },

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
    }))
});
