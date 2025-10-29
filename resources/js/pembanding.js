import L from "leaflet";
import "leaflet/dist/leaflet.css";
import "leaflet.markercluster";
import "leaflet.markercluster/dist/MarkerCluster.css";
import "leaflet.markercluster/dist/MarkerCluster.Default.css";

// Pastikan Leaflet sudah tersedia (CDN atau di-bundle)
// Definisikan fungsi global yang dipanggil dari Blade

window.initPembandingMap = function (points, domId) {
    try {
        if (typeof L === "undefined") {
            console.error("Leaflet belum ter-load");
            return;
        }

        const el = document.getElementById(domId);
        if (!el) return;

        // Hindari duplikasi map ketika halaman dipasang ulang
        if (el._leaflet_id) {
            el.innerHTML = "";
        }

        const map = L.map(domId, { zoomControl: true }).setView([-2.5, 118], 5);

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19,
            attribution: "&copy; OpenStreetMap contributors",
        }).addTo(map);

        const cluster =
            typeof L.markerClusterGroup === "function"
                ? L.markerClusterGroup()
                : L.layerGroup();

        const bounds = [];
        (points || []).forEach((p) => {
            if (typeof p.lat !== "number" || typeof p.lng !== "number") return;
            const m = L.marker([p.lat, p.lng]).bindPopup(
                `<div style="min-width:220px">
           <strong>Alamat</strong><br>${p.alamat ?? "-"}<br><br>
           <a href="${p.url}" class="underline">Lihat detail</a>
         </div>`
            );
            cluster.addLayer(m);
            bounds.push([p.lat, p.lng]);
        });

        map.addLayer(cluster);
        if (bounds.length) map.fitBounds(bounds, { padding: [30, 30] });
    } catch (e) {
        console.error("initPembandingMap error:", e);
    }
};
