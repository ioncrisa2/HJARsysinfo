<x-filament-widgets::widget>
    <x-filament::section>
        {{-- Custom Header: Judul di Kiri, Input di Kanan --}}
        <x-slot name="heading">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 w-full">

                {{-- Judul Widget --}}
                <div class="text-lg font-bold text-gray-800 dark:text-white">
                    Peta Sebaran Data Pembanding
                </div>

                {{-- Form Pencarian (Integrasi di Header) --}}
                <div class="flex items-center gap-2">

                    {{-- Input Latitude --}}
                    <div class="w-32">
                        <x-filament::input.wrapper>
                            <x-filament::input
                                type="number"
                                wire:model="latInput"
                                placeholder="Latitude"
                                step="0.000001"
                            />
                        </x-filament::input.wrapper>
                    </div>

                    {{-- Input Longitude --}}
                    <div class="w-32">
                        <x-filament::input.wrapper>
                            <x-filament::input
                                type="number"
                                wire:model="lngInput"
                                placeholder="Longitude"
                                step="0.000001"
                            />
                        </x-filament::input.wrapper>
                    </div>

                    {{-- Tombol Submit --}}
                    <x-filament::button wire:click="searchLocation" size="sm" icon="heroicon-m-magnifying-glass">
                        Cari
                    </x-filament::button>

                    {{-- Loading Indicator (Optional) --}}
                    <div wire:loading wire:target="searchLocation">
                        <x-filament::loading-indicator class="h-5 w-5 text-primary-500" />
                    </div>
                </div>
            </div>
        </x-slot>

        {{-- Area Peta --}}
        <div
            x-data="{
                map: null,
                initMap() {
                    // Logika bawaan library biasanya ada di sini atau di component parent
                    // Kita biarkan div ini menghandle render map dari library
                }
            }"
            wire:ignore.self
            {{-- wire:ignore.self penting agar saat livewire refresh, map tidak hancur --}}
        >
            {{--
                 Di sini kita panggil konten asli dari library.
                 Karena kita meng-override file view utamanya, kita harus memastikan
                 script render map bawaan library tetap berjalan.

                 Lihat isi asli file map-widget.blade.php Anda.
                 Biasanya isinya adalah div dengan ID map dan script Leaflet/Google.
                 Paste kode asli map rendering di bawah ini:
            --}}

            <div
                id="{{ $this->getMapId() }}"
                style="height: {{ $this->getHeight() }}; z-index: 1;"

                {{-- INI KUNCI AGAR MAP MENDETEKSI PERUBAHAN DARI LIVEWIRE --}}
                data-center="@json($this->getMapOptions()['center'])"
                data-zoom="@json($this->getMapOptions()['zoom'])"
                data-markers="@json($this->getMarkers())"

                x-data="mapWidget({
                    id: '{{ $this->getMapId() }}',
                    options: @json($this->getMapOptions()),
                    markers: @json($this->getMarkers()),
                    bounds: @json($this->getMapBounds()),
                    fitBounds: @json($this->getFitBounds()),
                    polylines: @json($this->getPolylines()),
                    polygons: @json($this->getPolygons()),
                    rectangles: @json($this->getRectangles()),
                    circles: @json($this->getCircles()),
                    tileLayerUrl: '{{ $this->getTileUrl() }}',
                    tileLayerOptions: @json($this->getTileLayerOptions()),
                    actions: @json($this->getActions()),
                })"
                x-init="init()"

                {{-- Listener agar map terupdate saat tombol cari ditekan --}}
                x-effect="
                    // Update Center & Zoom saat Livewire berubah
                    map.setView($wire.mapCenter, $wire.mapZoom);

                    // Re-draw markers (Logic ini mungkin perlu disesuaikan tergantung library)
                    // Tapi biasanya library webbingbrasil merender ulang marker via x-data markers
                "
            ></div>
        </div>

    </x-filament::section>
</x-filament-widgets::widget>
