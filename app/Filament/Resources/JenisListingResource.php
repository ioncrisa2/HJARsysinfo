<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JenisListingResource\Pages;
use App\Models\JenisListing;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Supports\Slug;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Filament\Clusters\MasterData;

class JenisListingResource extends Resource
{
    protected static ?string $model = JenisListing::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $cluster = MasterData::class;

    protected static ?string $modelLabel = 'Jenis Listing';
    protected static ?string $pluralModelLabel = 'Jenis Listing';

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

                    Forms\Components\Select::make('badge_color_token')
                        ->label('Badge Color')
                        ->helperText('Dipakai untuk badge status (optional).')
                        ->options([
                            'gray'    => 'Gray',
                            'primary' => 'Primary',
                            'info'    => 'Info',
                            'success' => 'Success',
                            'warning' => 'Warning',
                            'danger'  => 'Danger',
                        ])
                        ->searchable()
                        ->placeholder('Auto (default gray)')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('marker_icon_url')
                        ->label('Marker Icon URL')
                        ->helperText('Dipakai untuk icon marker di map (optional).')
                        ->url()
                        ->maxLength(1000)
                        ->placeholder('https://.../marker-icon-2x-blue.png')
                        ->columnSpanFull(),
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

                Tables\Columns\TextColumn::make('badge_color_token')
                    ->label('Badge')
                    ->badge()
                    ->color(fn (?string $state) => $state ?: 'gray')
                    ->formatStateUsing(fn (?string $state) => $state ?: 'auto')
                    ->toggleable(isToggledHiddenByDefault: true),

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
            'index'  => Pages\ListJenisListings::route('/'),
            'create' => Pages\CreateJenisListing::route('/create'),
            'edit'   => Pages\EditJenisListing::route('/{record}/edit'),
        ];
    }
}
