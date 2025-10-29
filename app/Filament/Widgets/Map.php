<?php

namespace App\Filament\Widgets;

use App\Models\Pembanding;
use Webbingbrasil\FilamentMaps\Marker;
use Webbingbrasil\FilamentMaps\Actions;
use Webbingbrasil\FilamentMaps\Widgets\MapWidget;
use App\Filament\Resources\DataPembandingResource;
use Illuminate\Contracts\Support\Htmlable;

class Map extends MapWidget
{
    protected int | string | array $columnSpan = 2;
    protected bool $hasBorder = false;
    protected string|Htmlable|null $heading = "Peta Sebaran Data Pembanding";

    protected string | array  $tileLayerUrl = [
        'MapTiler' => 'https://api.maptiler.com/tiles/satellite-v2/{z}/{x}/{y}.jpg?key=tsNmu1udsggoxQXYTlrP',
        'OpenStreetMap' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    ];

    protected array $tileLayerOptions = [
        'MapTiler' => [
            'attribution' => 'Map data © <a href="https://api.maptiler.com">Map Tiler</a> contributors)',
        ],
        'OpenStreetMap' => [
            'attribution' => 'Map data © <a href="https://openstreetmap.org">OpenStreetMap</a> contributors',
        ],
    ];

    public function getMarkers(): array
    {
        $pembandingData = Pembanding::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get(['id', 'alamat_data', 'latitude', 'longitude','image']);


            return $pembandingData->map(function ($data) {
            $lat = (float) $data->latitude;
            $lng = (float) $data->longitude;

            $url = DataPembandingResource::getUrl('view',['record' => $data]);

            $alamat = $data->alamat_data ?? '-';
            $imgUrl = $data->image ?? 'https://via.placeholder.com/200x120?text=No+Image';

            $popupContent = <<<HTML
                <div style="width: 200px; padding: 5px;">
                    <img src="{$imgUrl}" style="width:100%; height:auto; border-radius: 4px; margin-bottom: 8px;" alt="Gambar Data">
                    <p style="margin: 0;"><strong>Alamat:</strong></p>
                    <p style="margin: 0 0 8px 0;">{$alamat}</p>
                    <a href="{$url}" target="_blank" style="color: #4f46e5; text-decoration: underline;">Lihat Detail Data</a>
                </div>
            HTML;

            return Marker::make('pembanding-' . $data->id)
                ->lat($lat)
                ->lng($lng)
                ->popup($popupContent);
        })->toArray();
    }

    public function getActions(): array
    {
        return [
            Actions\ZoomAction::make(),
            Actions\CenterMapAction::make()->zoom(2),
            Actions\Action::make('mode')
                ->icon('filamentmapsicon-o-square-3-stack-3d')
                ->callback('setTileLayer(mode === "OpenStreetMap" ? "MapTiler" : "OpenStreetMap")')
        ];
    }

    public function getMapOptions(): array
    {
        return [
            'center' => [-2.5, 118], // Tengah Indonesia
            'zoom' => 5,
        ];
    }

    protected function getTileUrl(): string
    {
        return "https://api.maptiler.com/tiles/satellite-v2/{z}/{x}/{y}.jpg?key=tsNmu1udsggoxQXYTlrP";
    }
}
