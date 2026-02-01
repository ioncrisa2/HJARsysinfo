<?php

namespace App\Filament\Resources\DokumenTanahResource\Pages;

use App\Filament\Resources\DokumenTanahResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDokumenTanahs extends ListRecords
{
    protected static string $resource = DokumenTanahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
