<?php

namespace App\Filament\Resources\TrashedNonPropertyComparableResource\Pages;

use App\Filament\Resources\TrashedNonPropertyComparableResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTrashedNonPropertyComparable extends ViewRecord
{
    protected static string $resource = TrashedNonPropertyComparableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\RestoreAction::make()
                ->successRedirectUrl(TrashedNonPropertyComparableResource::getUrl('index')),

            Actions\ForceDeleteAction::make()
                ->requiresConfirmation()
                ->successRedirectUrl(TrashedNonPropertyComparableResource::getUrl('index')),
        ];
    }
}

