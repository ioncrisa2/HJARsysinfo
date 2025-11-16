<?php

namespace App\Filament\Resources\DataPembandingResource\Pages;

use Filament\Actions;
use Filament\Infolists\Components\Grid;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use App\Filament\Resources\PembandingResource;
use App\Filament\Resources\DataPembandingResource;
use Filament\Infolists\Components\RepeatableEntry;
use Nben\FilamentRecordNav\Actions\NextRecordAction;
use Nben\FilamentRecordNav\Actions\PreviousRecordAction;
use Nben\FilamentRecordNav\Concerns\WithRecordNavigation;

class ViewDataPembanding extends ViewRecord
{
    use WithRecordNavigation;

    protected static string $resource = DataPembandingResource::class;
    protected static ?string $title = 'Detail Data Pembanding';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('history')
                ->label('Riwayat Perubahan')
                ->icon('heroicon-o-clock')
                ->color('gray')
                ->modalHeading('Riwayat Perubahan Data')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup')
                ->slideOver()
                ->infolist([
                   RepeatableEntry::make('activities')
                        ->hiddenLabel()
                        ->contained(false)
                        ->schema([
                            Section::make()
                                ->compact()
                                ->schema([
                                    Grid::make(3)->schema([
                                        TextEntry::make('created_at')
                                            ->label('Waktu')
                                            ->dateTime('d M Y, H:i:s', timezone: 'Asia/Jakarta')
                                            ->icon('heroicon-m-calendar')
                                            ->color('gray'),
                                        TextEntry::make('causer.name')
                                            ->label('Oleh')
                                            ->icon('heroicon-m-user')
                                            ->placeholder('Sistem'),
                                        TextEntry::make('event')
                                            ->label('Aksi')
                                            ->badge()
                                            ->color(fn (string $state): string => match ($state) {
                                                'created' => 'success',
                                                'updated' => 'warning',
                                                'deleted' => 'danger',
                                                default => 'gray',
                                            })
                                            ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                                    ]),

                                    ViewEntry::make('properties')
                                        ->label('Rincian Perubahan')
                                        ->view('infolists.components.activity-log-table')
                                        ->visible(fn ($record) => $record->event === 'updated' && !empty($record->properties)),
                                ])
                        ]),
                ]),
            Actions\EditAction::make(),
            Actions\Action::make('map')
                ->label('Buka Peta')
                ->icon('heroicon-o-map-pin')
                ->color('info')
                ->url(fn () => $this->record->latitude && $this->record->longitude
                    ? "https://www.google.com/maps?q={$this->record->latitude},{$this->record->longitude}"
                    : null,
                true)
                ->disabled(fn () => ! ($this->record->latitude && $this->record->longitude)),
            PreviousRecordAction::make()
                ->label('← Previous')
                ->color('secondary')
                ->size('sm'),

            NextRecordAction::make()
                ->label('Next →')
                ->color('secondary')
                ->size('sm'),
        ];
    }
}
