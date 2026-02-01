<?php

namespace App\Filament\Widgets;

use App\Models\JenisListing;
use App\Models\Pembanding;
use Filament\Widgets\ChartWidget;

class ListingCompositionChart extends ChartWidget
{
    protected static ?string $heading = 'Komposisi Listing';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        // Count pembanding grouped by jenis_listing_id
        $countsById = Pembanding::query()
            ->selectRaw('jenis_listing_id, COUNT(*) as total')
            ->whereNotNull('jenis_listing_id')
            ->groupBy('jenis_listing_id')
            ->pluck('total', 'jenis_listing_id'); // [id => total]

        // Pull listing master data in a stable order
        $listings = JenisListing::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'badge_color']);

        $labels = [];
        $data   = [];
        $colors = [];

        foreach ($listings as $listing) {
            $labels[] = $listing->name;
            $data[]   = (int) ($countsById[$listing->id] ?? 0);
            $colors[] = $listing->badge_color ?: '#64748b';
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Data',
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'hoverOffset' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
