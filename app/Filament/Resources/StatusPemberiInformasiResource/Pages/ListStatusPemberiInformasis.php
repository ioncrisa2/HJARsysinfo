<?php

namespace App\Filament\Resources\StatusPemberiInformasiResource\Pages;

use App\Filament\Resources\StatusPemberiInformasiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStatusPemberiInformasis extends ListRecords
{
    protected static string $resource = StatusPemberiInformasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
