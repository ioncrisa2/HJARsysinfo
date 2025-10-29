<?php

namespace App\Filament\Resources\DataPembandingResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\PembandingResource;
use App\Filament\Resources\DataPembandingResource;
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
