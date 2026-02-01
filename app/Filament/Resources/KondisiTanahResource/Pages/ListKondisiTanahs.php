<?php

namespace App\Filament\Resources\KondisiTanahResource\Pages;

use App\Filament\Resources\KondisiTanahResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKondisiTanahs extends ListRecords
{
    protected static string $resource = KondisiTanahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
