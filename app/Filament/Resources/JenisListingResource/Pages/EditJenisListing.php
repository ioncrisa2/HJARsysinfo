<?php

namespace App\Filament\Resources\JenisListingResource\Pages;

use App\Filament\Resources\JenisListingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJenisListing extends EditRecord
{
    protected static string $resource = JenisListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
