<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrashedNonPropertyComparableResource\Pages;
use App\Models\NonPropertyComparable;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TrashedNonPropertyComparableResource extends Resource
{
    protected static ?string $model = NonPropertyComparable::class;

    protected static ?string $navigationIcon = 'heroicon-o-trash';
    protected static ?string $navigationGroup = 'Bank Data';
    protected static ?string $navigationLabel = 'Deleted Non Properti';
    protected static ?string $pluralLabel = 'Deleted Non Properti';
    protected static ?int $navigationSort = 6;

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
            ->defaultSort('deleted_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('comparable_code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('asset_category')
                    ->label('Kategori')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::assetCategoryLabel($state)),

                Tables\Columns\TextColumn::make('brand')
                    ->label('Unit')
                    ->searchable()
                    ->description(fn (NonPropertyComparable $record): string => trim(implode(' ', array_filter([
                        $record->model,
                        $record->variant,
                    ])))),

                Tables\Columns\TextColumn::make('listing_type')
                    ->label('Listing')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::listingTypeLabel($state)),

                Tables\Columns\TextColumn::make('asking_price')
                    ->label('Harga')
                    ->alignRight()
                    ->state(fn (NonPropertyComparable $record) => $record->asking_price ?? $record->transaction_price)
                    ->formatStateUsing(fn ($state, NonPropertyComparable $record): string => self::formatPrice($state, $record->currency)),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Dihapus')
                    ->since()
                    ->sortable(),

                Tables\Columns\TextColumn::make('deletedBy.name')
                    ->label('Dihapus Oleh')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('deleted_reason')
                    ->label('Alasan')
                    ->limit(50)
                    ->wrap(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Detail'),
                Tables\Actions\RestoreAction::make()
                    ->successRedirectUrl(fn (): string => static::getUrl('index')),
                Tables\Actions\ForceDeleteAction::make()
                    ->requiresConfirmation()
                    ->successRedirectUrl(fn (): string => static::getUrl('index')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Data Unit')
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
                        TextEntry::make('data_date')->label('Tanggal Data')->date('d M Y'),
                    ]),

                Section::make('Info Penghapusan')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('deleted_at')->label('Waktu Dihapus')->dateTime('d M Y H:i'),
                        TextEntry::make('deletedBy.name')->label('Dihapus Oleh'),
                        TextEntry::make('deleted_reason')->label('Alasan')->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->onlyTrashed()
            ->with(['deletedBy:id,name']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrashedNonPropertyComparables::route('/'),
            'view' => Pages\ViewTrashedNonPropertyComparable::route('/{record}'),
        ];
    }

    private static function canAccessResource(): bool
    {
        return (bool) auth()->user()?->hasRole('super_admin');
    }

    private static function assetCategoryLabel(?string $state): string
    {
        return match ($state) {
            'vehicle' => 'Kendaraan',
            'heavy_equipment' => 'Alat Berat',
            'barge' => 'Tongkang',
            default => '-',
        };
    }

    private static function listingTypeLabel(?string $state): string
    {
        return match ($state) {
            'penawaran' => 'Penawaran',
            'transaksi' => 'Transaksi',
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

