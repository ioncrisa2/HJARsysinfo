<?php

namespace App\Filament\Resources\TrashedDataPembandingResource\Pages;

use App\Filament\Resources\TrashedDataPembandingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTrashedDataPembanding extends ViewRecord
{
    protected static string $resource = TrashedDataPembandingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\RestoreAction::make()
                ->successRedirectUrl(TrashedDataPembandingResource::getUrl('index')),

            Actions\ForceDeleteAction::make()
                ->requiresConfirmation()
                ->successRedirectUrl(TrashedDataPembandingResource::getUrl('index')),
        ];
    }
}
