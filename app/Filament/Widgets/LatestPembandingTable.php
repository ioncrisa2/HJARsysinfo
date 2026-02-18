<?php

namespace App\Filament\Widgets;

use App\Models\Pembanding;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\JenisListing;

class LatestPembandingTable extends BaseWidget
{
    protected static ?string $heading = 'Data Terbaru Masuk';
    protected static ?int $sort = 2;
    
    protected int|string|array $columnSpan = [
        'default' => 'full',
        'md' => 'full',
        'sm' => 'full'
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Pembanding::query()->latest()->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('alamat_data')
                    ->label('Alamat')
                    ->limit(50)
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('harga')
                    ->label('Harga')
                    ->money('IDR')
                    ->sortable(),

               Tables\Columns\TextColumn::make('jenisListing.name')
                    ->badge()
                    ->color(fn (Pembanding $record) => $record->jenisListing?->badge_color ?? 'gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Input')
                    ->since()
                    ->color('gray'),
            ])
            ->paginated(false);
    }
}
