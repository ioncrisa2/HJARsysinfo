<x-filament-widgets::widget>
    {{-- resources/views/filament/widgets/pembanding-map-widget.blade.php --}}
    <x-filament::section>
        <x-slot name="heading">Sebaran Koordinat Data Pembanding</x-slot>

        <div id="{{ $domId }}" wire:ignore style="height:520px;border-radius:16px;overflow:hidden"></div>

        @push('scripts')
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

            <script>
                let initPembandingMap = function(points, domId) {
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

                        const map = L.map(domId, {
                            zoomControl: true
                        }).setView([-2.5, 118], 5);

                        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                            maxZoom: 19,
                            attribution: "&copy; OpenStreetMap contributors",
                        }).addTo(map);

                        const cluster =
                            typeof L.markerClusterGroup === "function" ?
                            L.markerClusterGroup() :
                            L.layerGroup();

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
                        if (bounds.length) map.fitBounds(bounds, {
                            padding: [30, 30]
                        });
                    } catch (e) {
                        console.error("initPembandingMap error:", e);
                    }
                };

                // panggil saat pertama render
                document.addEventListener('DOMContentLoaded', function() {
                    if (window.initPembandingMap) {
                        window.initPembandingMap(@json($points), @json($domId));
                    }
                });
                // panggil lagi kalau pakai Livewire navigate
                document.addEventListener('livewire:navigated', function() {
                    if (window.initPembandingMap) {
                        window.initPembandingMap(@json($points), @json($domId));
                    }
                });
            </script>
        @endpush
    </x-filament::section>

</x-filament-widgets::widget>
