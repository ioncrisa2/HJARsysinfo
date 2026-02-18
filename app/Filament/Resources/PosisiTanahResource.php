<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PosisiTanahResource\Pages;
use App\Filament\Resources\PosisiTanahResource\RelationManagers;
use App\Models\PosisiTanah;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Supports\Slug;
use App\Filament\Clusters\MasterData;

class PosisiTanahResource extends Resource
{
    protected static ?string $model = PosisiTanah::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $cluster = MasterData::class;
    protected static ?int $navigationSort = 15;

    protected static ?string $modelLabel = 'Posisi Tanah';
    protected static ?string $pluralModelLabel = 'Posisi Tanah';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Data')
                ->columns(2)
                ->schema([
                      Forms\Components\TextInput::make('name')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (?string $state, Set $set, Get $get) {
                            $set('slug', Slug::snake($state));
                        }),

                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->regex('/^[a-z0-9]+(?:_[a-z0-9]+)*$/')
                        ->unique(ignoreRecord: true)
                        ->disabled()
                        ->dehydrated(true),

                    Forms\Components\TextInput::make('sort_order')
                        ->label('Urutan')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Update')
                    ->dateTime()
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktif')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
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
            'index'  => Pages\ListPosisiTanahs::route('/'),
            'create' => Pages\CreatePosisiTanah::route('/create'),
            'edit'   => Pages\EditPosisiTanah::route('/{record}/edit'),
        ];
    }
}
