<?php

namespace App\Filament\Resources\JenisObjekResource\Pages;

use App\Filament\Resources\JenisObjekResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJenisObjeks extends ListRecords
{
    protected static string $resource = JenisObjekResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
