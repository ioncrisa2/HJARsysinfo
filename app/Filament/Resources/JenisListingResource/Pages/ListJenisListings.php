<?php

namespace App\Filament\Resources\JenisListingResource\Pages;

use App\Filament\Resources\JenisListingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJenisListings extends ListRecords
{
    protected static string $resource = JenisListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
