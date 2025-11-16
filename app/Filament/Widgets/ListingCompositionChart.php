<?php

namespace App\Filament\Widgets;

use App\Models\Pembanding;
use Filament\Widgets\ChartWidget;

class ListingCompositionChart extends ChartWidget
{
    protected static ?string $heading = 'Komposisi Listing';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $transaksi = Pembanding::where('jenis_listing', 'transaksi')->count();
        $penawaran = Pembanding::where('jenis_listing', 'penawaran')->count();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Data',
                    'data' => [$transaksi, $penawaran],
                    'backgroundColor' => [
                        '#16a34a',
                        '#ea580c',
                    ],
                    'hoverOffset' => 4,
                ],
            ],
            'labels' => ['Transaksi', 'Penawaran'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
