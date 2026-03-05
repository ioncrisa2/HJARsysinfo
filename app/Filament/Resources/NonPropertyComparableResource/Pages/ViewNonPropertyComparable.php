<?php

namespace App\Filament\Resources\NonPropertyComparableResource\Pages;

use App\Filament\Resources\NonPropertyComparableResource;
use App\Models\NonPropertyComparable;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Spatie\Activitylog\Models\Activity;

class ViewNonPropertyComparable extends ViewRecord
{
    protected static string $resource = NonPropertyComparableResource::class;

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
                            ->where('subject_type', NonPropertyComparable::class)
                            ->where('subject_id', $this->record->getKey())
                            ->with('causer:id,name')
                            ->latest('created_at')
                            ->limit(30)
                            ->get(),
                    ],
                )),

            Actions\Action::make('open_app')
                ->label('Buka di App')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn (): string => route('home.non-properti.show', $this->record), shouldOpenInNewTab: true),
        ];
    }
}

