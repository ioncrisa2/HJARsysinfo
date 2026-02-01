<?php

namespace App\Filament\Resources\DokumenTanahResource\Pages;

use App\Filament\Resources\DokumenTanahResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDokumenTanah extends EditRecord
{
    protected static string $resource = DokumenTanahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
