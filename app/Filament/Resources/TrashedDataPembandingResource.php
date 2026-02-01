<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Pembanding;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\TrashedDataPembanding;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\TrashedDataPembandingResource\Pages;
use App\Filament\Resources\TrashedDataPembandingResource\RelationManagers;

class TrashedDataPembandingResource extends Resource
{
    protected static ?string $model = Pembanding::class;
    protected static ?string $navigationIcon = 'heroicon-o-trash';
    protected static ?string $navigationGroup = 'Pelindung';
    protected static ?string $navigationLabel = 'Deleted Data';
    protected static ?string $modelLabel = 'Data Pembanding yang dihapus';

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) auth()->user()?->hasRole('super_admin');
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->hasRole('super_admin');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->onlyTrashed();
    }

    // Not used (no create/edit), but keep to satisfy Filament signatures
    public static function form(Form $form): Form
    {
        return $form;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('deleted_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('alamat_data')
                    ->label('Alamat')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('jenis_listing')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(?string $state) => $state ? ucfirst($state) : '-')
                    ->color(fn(?string $state): string => match ($state) {
                        'penawaran' => 'warning',
                        'transaksi' => 'success',
                        'sewa'      => 'info',
                        default     => 'gray',
                    }),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Dihapus')
                    ->dateTime()
                    ->since()
                    ->sortable(),

                Tables\Columns\TextColumn::make('deleted_by.name')
                    ->label('Dihapus Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deleted_reason')
                    ->label('Alasan')
                    ->limit(40)
                    ->wrap(),
            ])
            ->actions([
                Tables\Actions\RestoreAction::make()
                    ->successRedirectUrl(fn() => static::getUrl('index')),
                Tables\Actions\ForceDeleteAction::make()
                    ->requiresConfirmation()
                    ->successRedirectUrl(fn() => static::getUrl('index')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrashedDataPembandings::route('/'),
            'view'  => Pages\ViewTrashedDataPembanding::route('/{record}'),
        ];
    }
}
