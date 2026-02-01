<?php

namespace App\Filament\Resources\JenisObjekResource\Pages;

use App\Filament\Resources\JenisObjekResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJenisObjek extends EditRecord
{
    protected static string $resource = JenisObjekResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
