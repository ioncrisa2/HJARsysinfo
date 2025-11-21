<?php

namespace App\Filament\Resources\DataPembandingResource\Pages;

use App\Filament\Resources\DataPembandingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDataPembanding extends CreateRecord
{
    protected static string $resource = DataPembandingResource::class;

    protected function shouldPersistDataInSession(): bool
    {
        return true;
    }
}
