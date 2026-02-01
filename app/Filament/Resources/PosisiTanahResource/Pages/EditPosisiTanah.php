<?php

namespace App\Filament\Resources\PosisiTanahResource\Pages;

use App\Filament\Resources\PosisiTanahResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPosisiTanah extends EditRecord
{
    protected static string $resource = PosisiTanahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
