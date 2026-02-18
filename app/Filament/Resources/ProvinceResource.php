<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\DataLokasi;
use App\Filament\Resources\ProvinceResource\Pages;
use App\Models\Province;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProvinceResource extends Resource
{
    protected static ?string $model = Province::class;

    protected static ?string $cluster = DataLokasi::class;
    protected static ?string $navigationIcon = 'heroicon-o-flag';
    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Provinsi';
    protected static ?string $pluralModelLabel = 'Provinsi';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Data Provinsi')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('id')
                        ->label('Kode Provinsi')
                        ->required()
                        ->maxLength(2)
                        ->unique(ignoreRecord: true)
                        ->disabledOn('edit')
                        ->dehydrated(true),

                    Forms\Components\TextInput::make('name')
                        ->label('Nama Provinsi')
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
                    ->label('Nama Provinsi')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('regencies_count')
                    ->label('Jumlah Kab/Kota')
                    ->counts('regencies')
                    ->sortable(),
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
            'index' => Pages\ListProvinces::route('/'),
            'create' => Pages\CreateProvince::route('/create'),
            'edit' => Pages\EditProvince::route('/{record}/edit'),
        ];
    }
}
