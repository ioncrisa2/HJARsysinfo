<?php

namespace App\Filament\Resources\KondisiTanahResource\Pages;

use App\Filament\Resources\KondisiTanahResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKondisiTanah extends EditRecord
{
    protected static string $resource = KondisiTanahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
