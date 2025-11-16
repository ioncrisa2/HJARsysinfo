<?php

namespace App\Filament\Widgets;

use App\Models\Pembanding;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Enums\JenisListing; // Pastikan import Enum Anda

class LatestPembandingTable extends BaseWidget
{
    protected static ?string $heading = 'Data Terbaru Masuk';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

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
                    ->money('IDR', divideBy: 100)
                    ->sortable(),

                Tables\Columns\TextColumn::make('jenis_listing')
                    ->label('Status')
                    ->badge()
                    ->color(fn (JenisListing $state): string => match ($state) {
                        JenisListing::Penawaran => 'success',
                        JenisListing::Transaksi => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Input')
                    ->since()
                    ->color('gray'),
            ])
            ->paginated(false);
    }
}
