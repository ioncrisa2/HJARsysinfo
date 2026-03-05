<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NonPropertyComparableResource\Pages;
use App\Models\NonPropertyComparable;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NonPropertyComparableResource extends Resource
{
    protected static ?string $model = NonPropertyComparable::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Bank Data';
    protected static ?string $navigationLabel = 'Data Non Properti';
    protected static ?string $pluralLabel = 'Data Non Properti';
    protected static ?int $navigationSort = 5;

    public static function shouldRegisterNavigation(): bool
    {
        return self::canAccessResource();
    }

    public static function canViewAny(): bool
    {
        return self::canAccessResource();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('data_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('comparable_code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('asset_category')
                    ->label('Kategori')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::assetCategoryLabel($state)),

                Tables\Columns\TextColumn::make('asset_subtype')
                    ->label('Subjenis')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('brand')
                    ->label('Unit')
                    ->searchable()
                    ->description(fn (NonPropertyComparable $record): string => trim(implode(' ', array_filter([
                        $record->model,
                        $record->variant,
                    ])))),

                Tables\Columns\TextColumn::make('listing_type')
                    ->label('Jenis Listing')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::listingTypeLabel($state)),

                Tables\Columns\TextColumn::make('asking_price')
                    ->label('Harga')
                    ->alignRight()
                    ->state(fn (NonPropertyComparable $record) => $record->asking_price ?? $record->transaction_price)
                    ->formatStateUsing(fn ($state, NonPropertyComparable $record): string => self::formatPrice($state, $record->currency)),

                Tables\Columns\TextColumn::make('location_city')
                    ->label('Kota')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('verification_status')
                    ->label('Verifikasi')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::verificationLabel($state))
                    ->color(fn (?string $state): string => match ($state) {
                        'verified' => 'success',
                        'partial' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('media_count')
                    ->label('Media')
                    ->counts('media')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('data_date')
                    ->label('Tanggal Data')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('asset_category')
                    ->label('Kategori')
                    ->options(self::assetCategoryOptions()),

                Tables\Filters\SelectFilter::make('listing_type')
                    ->label('Jenis Listing')
                    ->options([
                        'penawaran' => 'Penawaran',
                        'transaksi' => 'Transaksi',
                    ]),

                Tables\Filters\SelectFilter::make('verification_status')
                    ->label('Verifikasi')
                    ->options([
                        'unverified' => 'Belum Verifikasi',
                        'partial' => 'Verifikasi Parsial',
                        'verified' => 'Terverifikasi',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('open_app')
                    ->label('Buka App')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (NonPropertyComparable $record): string => route('home.non-properti.show', $record), shouldOpenInNewTab: true),
                Tables\Actions\ViewAction::make()
                    ->label('Detail'),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Unit')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('comparable_code')->label('Kode'),
                        TextEntry::make('asset_category')
                            ->label('Kategori')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => self::assetCategoryLabel($state)),
                        TextEntry::make('asset_subtype')->label('Subjenis'),
                        TextEntry::make('brand')->label('Merek'),
                        TextEntry::make('model')->label('Model'),
                        TextEntry::make('variant')->label('Varian'),
                        TextEntry::make('manufacture_year')->label('Tahun'),
                        TextEntry::make('serial_number')->label('Serial/Hull'),
                        TextEntry::make('registration_number')->label('Registrasi'),
                    ]),

                Section::make('Harga dan Verifikasi')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('listing_type')
                            ->label('Jenis Listing')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => self::listingTypeLabel($state)),
                        TextEntry::make('price')
                            ->label('Harga')
                            ->state(fn (NonPropertyComparable $record): string => self::formatPrice(
                                $record->asking_price ?? $record->transaction_price,
                                $record->currency
                            )),
                        TextEntry::make('currency')->label('Mata Uang'),
                        TextEntry::make('assumed_discount_percent')
                            ->label('Diskon Asumsi')
                            ->state(fn (?float $state): string => $state === null ? '-' : number_format($state, 2) . '%'),
                        TextEntry::make('data_date')->label('Tanggal Data')->date('d M Y'),
                        TextEntry::make('verification_status')
                            ->label('Status Verifikasi')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => self::verificationLabel($state)),
                    ]),

                Section::make('Sumber dan Lokasi')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('source_name')->label('Nama Sumber'),
                        TextEntry::make('source_phone')->label('Telepon'),
                        TextEntry::make('source_platform')->label('Platform'),
                        TextEntry::make('source_url')
                            ->label('URL Sumber')
                            ->url(fn (?string $state): ?string => $state)
                            ->openUrlInNewTab(),
                        TextEntry::make('location_country')->label('Negara'),
                        TextEntry::make('location_city')->label('Kota'),
                        TextEntry::make('location_address')->label('Alamat')->columnSpanFull(),
                    ]),

                Section::make('Spesifikasi')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('vehicleSpec.vehicle_type')->label('Tipe Kendaraan'),
                        TextEntry::make('vehicleSpec.odometer_km')
                            ->label('Odometer')
                            ->state(fn ($state): string => $state === null ? '-' : number_format((float) $state, 0, ',', '.') . ' km'),
                        TextEntry::make('heavyEquipmentSpec.equipment_type')->label('Tipe Alat Berat'),
                        TextEntry::make('heavyEquipmentSpec.hour_meter')
                            ->label('Hour Meter')
                            ->state(fn ($state): string => $state === null ? '-' : number_format((float) $state, 0, ',', '.') . ' jam'),
                        TextEntry::make('bargeSpec.barge_type')->label('Tipe Tongkang'),
                        TextEntry::make('bargeSpec.capacity_dwt')
                            ->label('Kapasitas DWT')
                            ->state(fn ($state): string => $state === null ? '-' : number_format((float) $state, 0, ',', '.') . ' DWT'),
                    ]),

                Section::make('Metadata')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('media_count')->label('Jumlah Media')->state(fn (NonPropertyComparable $record): int => (int) $record->media()->count()),
                        TextEntry::make('creator.name')->label('Dibuat Oleh'),
                        TextEntry::make('updater.name')->label('Diupdate Oleh'),
                        TextEntry::make('created_at')->label('Dibuat Pada')->dateTime('d M Y H:i'),
                        TextEntry::make('updated_at')->label('Diupdate Pada')->dateTime('d M Y H:i'),
                    ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'vehicleSpec',
                'heavyEquipmentSpec',
                'bargeSpec',
                'creator:id,name',
                'updater:id,name',
            ])
            ->withCount('media');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNonPropertyComparables::route('/'),
            'view' => Pages\ViewNonPropertyComparable::route('/{record}'),
        ];
    }

    private static function canAccessResource(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('super_admin')
            || $user->can('view_any_data::non_property_comparable');
    }

    /**
     * @return array<string, string>
     */
    private static function assetCategoryOptions(): array
    {
        return [
            'vehicle' => 'Kendaraan',
            'heavy_equipment' => 'Alat Berat',
            'barge' => 'Tongkang',
        ];
    }

    private static function assetCategoryLabel(?string $state): string
    {
        if (! $state) {
            return '-';
        }

        return self::assetCategoryOptions()[$state] ?? $state;
    }

    private static function listingTypeLabel(?string $state): string
    {
        return match ($state) {
            'penawaran' => 'Penawaran',
            'transaksi' => 'Transaksi',
            default => '-',
        };
    }

    private static function verificationLabel(?string $state): string
    {
        return match ($state) {
            'verified' => 'Terverifikasi',
            'partial' => 'Verifikasi Parsial',
            'unverified' => 'Belum Verifikasi',
            default => '-',
        };
    }

    private static function formatPrice(mixed $amount, ?string $currency): string
    {
        if ($amount === null || $amount === '') {
            return '-';
        }

        $iso = strtoupper((string) ($currency ?: 'IDR'));

        return trim($iso . ' ' . number_format((float) $amount, 0, ',', '.'));
    }
}

