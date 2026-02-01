<?php

namespace App\Filament\Resources\PeruntukanResource\Pages;

use App\Filament\Resources\PeruntukanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPeruntukans extends ListRecords
{
    protected static string $resource = PeruntukanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
