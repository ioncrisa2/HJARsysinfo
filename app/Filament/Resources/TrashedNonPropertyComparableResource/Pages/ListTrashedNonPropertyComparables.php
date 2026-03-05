<?php

namespace App\Filament\Resources\TrashedNonPropertyComparableResource\Pages;

use App\Filament\Resources\TrashedNonPropertyComparableResource;
use Filament\Resources\Pages\ListRecords;

class ListTrashedNonPropertyComparables extends ListRecords
{
    protected static string $resource = TrashedNonPropertyComparableResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

