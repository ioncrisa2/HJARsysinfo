<?php

namespace App\Filament\Pages;

use App\Models\NonPropertyComparable;
use Filament\Pages\Page;

class NonPropertyBankData extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Bank Data';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Bank Data Non Properti';
    protected static ?string $title = 'Bank Data Non Properti';
    protected static ?string $slug = 'non-property-bank-data';

    protected static string $view = 'filament.pages.non-property-bank-data';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('super_admin')
            || $user->can('view_any_data::non_property_comparable');
    }

    protected function getViewData(): array
    {
        return [
            'total' => NonPropertyComparable::query()->count(),
            'verified' => NonPropertyComparable::query()
                ->where('verification_status', 'verified')
                ->count(),
            'lastDataDate' => NonPropertyComparable::query()->max('data_date'),
            'activeTotal' => NonPropertyComparable::query()->count(),
            'deletedTotal' => NonPropertyComparable::query()->onlyTrashed()->count(),
        ];
    }
}
