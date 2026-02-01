<?php

namespace App\Filament\Resources\StatusPemberiInformasiResource\Pages;

use App\Filament\Resources\StatusPemberiInformasiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStatusPemberiInformasi extends EditRecord
{
    protected static string $resource = StatusPemberiInformasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
