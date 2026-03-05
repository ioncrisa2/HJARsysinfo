<?php

namespace App\Filament\Resources\NonPropertyComparableResource\Pages;

use App\Filament\Resources\NonPropertyComparableResource;
use Filament\Resources\Pages\ListRecords;

class ListNonPropertyComparables extends ListRecords
{
    protected static string $resource = NonPropertyComparableResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

