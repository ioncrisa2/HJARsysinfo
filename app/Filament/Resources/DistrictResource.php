<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\DataLokasi;
use App\Filament\Resources\DistrictResource\Pages;
use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class DistrictResource extends Resource
{
    protected static ?string $model = District::class;

    protected static ?string $cluster = DataLokasi::class;
    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Kecamatan';
    protected static ?string $pluralModelLabel = 'Kecamatan';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Data Kecamatan')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('province_lookup_id')
                        ->label('Provinsi')
                        ->options(fn () => Province::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Forms\Components\Select $component, ?District $record): void {
                            $component->state($record?->regency?->province_id);
                        })
                        ->afterStateUpdated(function (Set $set): void {
                            $set('regency_id', null);
                        }),

                    Forms\Components\Select::make('regency_id')
                        ->label('Kabupaten / Kota')
                        ->options(function (Get $get): array {
                            $provinceId = $get('province_lookup_id');

                            if (blank($provinceId)) {
                                return [];
                            }

                            return Regency::query()
                                ->where('province_id', $provinceId)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all();
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabled(fn (Get $get): bool => blank($get('province_lookup_id'))),

                    Forms\Components\TextInput::make('id')
                        ->label('Kode Kecamatan')
                        ->maxLength(7)
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('Otomatis saat disimpan')
                        ->helperText('Kode dibuat otomatis berdasarkan Kabupaten / Kota terpilih.'),

                    Forms\Components\TextInput::make('name')
                        ->label('Nama Kecamatan')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Nama harus ditulis UPPERCASE (huruf kapital semua).')
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Str::upper(trim($state)) : null),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Kode')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Kecamatan')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('regency.name')
                    ->label('Kabupaten / Kota')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('regency.province.name')
                    ->label('Provinsi')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('villages_count')
                    ->label('Jumlah Desa/Kelurahan')
                    ->counts('villages')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('regency_id')
                    ->label('Kabupaten / Kota')
                    ->relationship('regency', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDistricts::route('/'),
            'create' => Pages\CreateDistrict::route('/create'),
            'edit' => Pages\EditDistrict::route('/{record}/edit'),
        ];
    }
}
