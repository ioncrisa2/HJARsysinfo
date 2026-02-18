<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\DataLokasi;
use App\Filament\Resources\RegencyResource\Pages;
use App\Models\Province;
use App\Models\Regency;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class RegencyResource extends Resource
{
    protected static ?string $model = Regency::class;

    protected static ?string $cluster = DataLokasi::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Kabupaten / Kota';
    protected static ?string $pluralModelLabel = 'Kabupaten / Kota';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Data Kabupaten / Kota')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('province_id')
                        ->label('Provinsi')
                        ->options(fn () => Province::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\TextInput::make('id')
                        ->label('Kode Kabupaten / Kota')
                        ->maxLength(4)
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('Otomatis saat disimpan')
                        ->helperText('Kode dibuat otomatis berdasarkan provinsi terpilih.'),

                    Forms\Components\TextInput::make('name')
                        ->label('Nama Kabupaten / Kota')
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
                    ->label('Nama Kabupaten / Kota')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('province.name')
                    ->label('Provinsi')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('districts_count')
                    ->label('Jumlah Kecamatan')
                    ->counts('districts')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('province_id')
                    ->label('Provinsi')
                    ->relationship('province', 'name')
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
            'index' => Pages\ListRegencies::route('/'),
            'create' => Pages\CreateRegency::route('/create'),
            'edit' => Pages\EditRegency::route('/{record}/edit'),
        ];
    }
}
