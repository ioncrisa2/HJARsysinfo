<?php

namespace App\Filament\Widgets;

use App\Enums\JenisListing;
use App\Models\Pembanding;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Storage;
use App\Filament\Resources\DataPembandingResource;

class Map extends Widget
{
    protected static string $view = 'filament.widgets.custom-map-widget';
    protected int|string|array $columnSpan = "full";

    public ?float $latInput = null;
    public ?float $lngInput = null;

    public int $radiusInput = 1000;

    public array $mapCenter = [-2.5489, 118.0149]; // Default Center (Indonesia)
    public int $mapZoom = 5;

    // Method dipanggil saat tombol "Cari" ditekan
    public function searchLocation()
    {
        $this->validate([
            'latInput' => 'required|numeric',
            'lngInput' => 'required|numeric',
        ]);

        $this->mapCenter = [(float) $this->latInput, (float) $this->lngInput];
        $this->mapZoom = 15;

        $this->dispatch('map-updated');
    }

    public function getAllMarkers(): array
    {
        // 1. Data Database (Biru/Abu)
        $markers = Pembanding::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get(['id', 'alamat_data', 'latitude', 'longitude', 'jenis_listing', 'image'])
            ->map(function ($item) {

                if ($item->image) {
                    $imgUrl = Storage::url($item->image);
                } else {
                    $imgUrl = 'https://placehold.co/600x400?text=No+Image';
                }

                $statusEnum =   $item->jenis_listing;
                $labelStatus = $statusEnum?->getLabel() ?? '-';

                $badgeColor = '#64748b'; // Abu-abu default
                $iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png';

                if ($statusEnum === JenisListing::Transaksi) {
                    $badgeColor = '#16a34a'; // Hijau
                    $iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png'; // Icon Hijau
                } elseif ($statusEnum === JenisListing::Penawaran) {
                    $badgeColor = '#ea580c'; // Orange
                    $iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-orange.png'; // Icon Orange
                }

                $url = DataPembandingResource::getUrl('view', ['record' => $item]);

                $popupHtml = <<<HTML
                    <div style="min-width: 220px; font-family: sans-serif;">

                        <div style="margin-bottom: 10px; border-radius: 6px; overflow: hidden; border: 1px solid #e5e7eb;">
                            <img src="{$imgUrl}"
                                 style="width: 100%; height: 120px; object-fit: cover; display: block;"
                                 alt="Foto Properti">
                        </div>

                        <div style="margin-bottom: 8px;">
                            <span style="background-color: {$badgeColor}; color: white;
                                         padding: 2px 8px; border-radius: 4px; font-size: 10px;
                                         font-weight: bold; text-transform: uppercase;">
                                {$labelStatus}
                            </span>
                        </div>
                        <div style="font-weight: bold; font-size: 13px; margin-bottom: 4px; line-height: 1.4;">
                            {$item->alamat_data}
                        </div>
                        <div style="margin-top: 10px;">
                            <a href="{$url}" target="_blank" style="color: #3b82f6; font-size: 12px; text-decoration: none; font-weight: 600;">
                               Lihat Detail &rarr;
                            </a>
                        </div>
                    </div>
                HTML;

                return [
                    'lat' => (float) $item->latitude,
                    'lng' => (float) $item->longitude,
                    'popup' => $popupHtml,
                    'icon' => $iconUrl
                ];
            })->toArray();

        if ($this->latInput && $this->lngInput) {
            $markers[] = [
                'lat' => (float) $this->latInput,
                'lng' => (float) $this->lngInput,
                'popup' => "<b>Lokasi Dicari</b><br>Lat: {$this->latInput}<br>Lng: {$this->lngInput}",
                'icon' => 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            ];
        }

        return $markers;
    }
}
