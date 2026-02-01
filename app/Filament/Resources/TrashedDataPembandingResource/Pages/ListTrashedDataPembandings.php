<?php

namespace App\Filament\Resources\TrashedDataPembandingResource\Pages;

use App\Filament\Resources\TrashedDataPembandingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTrashedDataPembandings extends ListRecords
{
    protected static string $resource = TrashedDataPembandingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
