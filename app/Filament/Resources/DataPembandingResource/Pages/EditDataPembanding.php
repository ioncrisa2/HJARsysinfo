<?php

namespace App\Filament\Resources\DataPembandingResource\Pages;

use App\Filament\Resources\DataPembandingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDataPembanding extends EditRecord
{
    protected static string $resource = DataPembandingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
