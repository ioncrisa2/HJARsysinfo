import "./bootstrap";
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';
import Alpine from 'alpinejs';

window.L = L;
window.Alpine = Alpine;

import iconUrl from 'leaflet/dist/images/marker-icon.png';
import iconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png';
import shadowUrl from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl,
    iconUrl,
    shadowUrl,
});

// Registrasi komponen Alpine yang sudah disederhanakan
Alpine.data('leafletMap', ({ markers, center, zoom }) => ({
    map: null,

    init() {
        // Inisialisasi peta
        this.map = L.map(this.$refs.map).setView(center, zoom);

        // Tambahkan tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(this.map);

        // Gunakan icon default Leaflet (biru) atau tetap hijau jika diinginkan.
        // Jika ingin tetap hijau seperti kode sebelumnya:
        const greenIcon = new L.Icon({
             iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
             shadowUrl,
             iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34]
        });

        // Loop markers dan tambahkan ke peta
        markers.forEach(marker => {
            L.marker([marker.lat, marker.lng], { icon: greenIcon })
                .bindPopup(marker.popup)
                .addTo(this.map);
        });
    }
}));

Alpine.start();
