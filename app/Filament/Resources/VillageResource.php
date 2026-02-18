<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\DataLokasi;
use App\Filament\Resources\VillageResource\Pages;
use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Village;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class VillageResource extends Resource
{
    protected static ?string $model = Village::class;

    protected static ?string $cluster = DataLokasi::class;
    protected static ?string $navigationIcon = 'heroicon-o-home-modern';
    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Desa / Kelurahan';
    protected static ?string $pluralModelLabel = 'Desa / Kelurahan';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Data Desa / Kelurahan')
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
                        ->afterStateHydrated(function (Forms\Components\Select $component, ?Village $record): void {
                            $component->state($record?->district?->regency?->province_id);
                        })
                        ->afterStateUpdated(function (Set $set): void {
                            $set('regency_lookup_id', null);
                            $set('district_id', null);
                        }),

                    Forms\Components\Select::make('regency_lookup_id')
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
                        ->live()
                        ->dehydrated(false)
                        ->disabled(fn (Get $get): bool => blank($get('province_lookup_id')))
                        ->afterStateHydrated(function (Forms\Components\Select $component, ?Village $record): void {
                            $component->state($record?->district?->regency_id);
                        })
                        ->afterStateUpdated(function (Set $set): void {
                            $set('district_id', null);
                        }),

                    Forms\Components\Select::make('district_id')
                        ->label('Kecamatan')
                        ->options(function (Get $get): array {
                            $regencyId = $get('regency_lookup_id');

                            if (blank($regencyId)) {
                                return [];
                            }

                            return District::query()
                                ->where('regency_id', $regencyId)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all();
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabled(fn (Get $get): bool => blank($get('regency_lookup_id'))),

                    Forms\Components\TextInput::make('id')
                        ->label('Kode Desa / Kelurahan')
                        ->maxLength(10)
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('Otomatis saat disimpan')
                        ->helperText('Kode dibuat otomatis berdasarkan kecamatan terpilih.'),

                    Forms\Components\TextInput::make('name')
                        ->label('Nama Desa / Kelurahan')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Nama harus ditulis UPPERCASE (huruf kapital semua).')
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Str::upper(trim($state)) : null)
                        ->columnSpanFull(),
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
                    ->label('Nama Desa / Kelurahan')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('district.name')
                    ->label('Kecamatan')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('district.regency.name')
                    ->label('Kabupaten / Kota')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('district.regency.province.name')
                    ->label('Provinsi')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('district_id')
                    ->label('Kecamatan')
                    ->relationship('district', 'name')
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
            'index' => Pages\ListVillages::route('/'),
            'create' => Pages\CreateVillage::route('/create'),
            'edit' => Pages\EditVillage::route('/{record}/edit'),
        ];
    }
}
