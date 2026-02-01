<?php

namespace App\Filament\Resources\BentukTanahResource\Pages;

use App\Filament\Resources\BentukTanahResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBentukTanahs extends ListRecords
{
    protected static string $resource = BentukTanahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
