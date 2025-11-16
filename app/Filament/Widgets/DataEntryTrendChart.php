<?php

namespace App\Filament\Widgets;

use App\Models\Pembanding;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class DataEntryTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Tren Input Data (Tahun Ini)';
    protected static ?int $sort = 2; // Urutan tampilan
    protected int | string | array $columnSpan = 'full'; // Memanjang penuh

    protected function getData(): array
    {
        // Mengambil data per bulan tahun ini
        $data = Trend::model(Pembanding::class)
            ->between(
                start: now()->startOfYear(),
                end: now()->endOfYear(),
            )
            ->perMonth()
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Data Masuk',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                    'borderColor' => '#3b82f6', // Biru
                    'fill' => true,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)', // Biru transparan
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
