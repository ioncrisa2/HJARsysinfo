<?php

namespace App\Filament\Resources\PosisiTanahResource\Pages;

use App\Filament\Resources\PosisiTanahResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPosisiTanahs extends ListRecords
{
    protected static string $resource = PosisiTanahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
