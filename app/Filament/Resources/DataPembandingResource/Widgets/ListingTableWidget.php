<?php

namespace App\Filament\Resources\DataPembandingResource\Widgets;

use App\Models\Pembanding;
use App\Models\JenisListing;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ListingTableWidget extends BaseWidget
{
    protected static ?string $heading = 'Komposisi Listing';
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $totalAll = (int) Pembanding::query()
            ->whereNotNull('jenis_listing_id')
            ->count();

        return $table
            ->query(
                JenisListing::query()
                    ->select(['id', 'name', 'badge_color', 'sort_order'])
                    ->withCount(['pembandings as total'])
                    // show only listings that have data (optional)
                    ->whereHas('pembandings')
                    ->orderByDesc('total')
                    ->orderBy('sort_order')
                    ->orderBy('name')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Jenis')
                    ->badge()
                    // NOTE: Filament badge color typically expects a named color (gray/success/etc).
                    // If badge_color is HEX, this won't map. You can leave it gray or switch to a ViewColumn for colored badge.
                    ->color('gray')
                    ->searchable(),

                Tables\Columns\TextColumn::make('total')
                    ->label('Jumlah')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('percent')
                    ->label('%')
                    ->state(function ($record) use ($totalAll) {
                        if ($totalAll <= 0) return '0%';
                        return number_format(($record->total / $totalAll) * 100, 1) . '%';
                    })
                    ->alignEnd(),

                Tables\Columns\ViewColumn::make('bar')
                    ->label('Proporsi')
                    ->view('filament.tables.columns.simple-bar')
                    ->state(function ($record) use ($totalAll) {
                        $pct = $totalAll > 0 ? ($record->total / $totalAll) : 0;

                        return [
                            'pct'   => $pct,
                            'color' => $record->badge_color ?: '#64748b',
                        ];
                    }),
            ]);
    }
}
