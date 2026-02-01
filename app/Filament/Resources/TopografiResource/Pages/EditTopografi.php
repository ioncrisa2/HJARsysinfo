<?php

namespace App\Filament\Resources\TopografiResource\Pages;

use App\Filament\Resources\TopografiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTopografi extends EditRecord
{
    protected static string $resource = TopografiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
