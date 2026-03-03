<?php

namespace App\Filament\Resources\DataPembandingResource\Pages;

use App\Filament\Resources\DataPembandingResource;
use Filament\Actions;
use Spatie\Activitylog\Models\Activity;
use Filament\Resources\Pages\ViewRecord;

class ViewDataPembanding extends ViewRecord
{
    protected static string $resource = DataPembandingResource::class;

    protected static string $view = 'filament.resources.data-pembanding-resource.pages.view-data-pembanding';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('history')
                ->label('History Perubahan')
                ->icon('heroicon-o-clock')
                ->color('gray')
                ->modalHeading('History Perubahan Data')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup')
                ->modalContent(fn () => view(
                    'filament.resources.data-pembanding-resource.partials.change-history',
                    [
                        'activities' => Activity::query()
                            ->where('subject_type', \App\Models\Pembanding::class)
                            ->where('subject_id', $this->record->getKey())
                            ->latest('created_at')
                            ->limit(30)
                            ->get(),
                    ],
                )),

            Actions\EditAction::make()
                ->label('Edit Data')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->url(fn () => DataPembandingResource::getUrl('edit', ['record' => $this->record])),

            Actions\DeleteAction::make()
                ->label('Hapus Data')
                ->icon('heroicon-o-trash')
                ->color('danger'),
        ];
    }
}
