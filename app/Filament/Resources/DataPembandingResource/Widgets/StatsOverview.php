<?php

namespace App\Filament\Resources\PembandingResource\Widgets;

use App\Models\Pembanding;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected ?string $heading = 'Analytics';
    

    protected function getStats(): array
    {
        $lasts3Days = Pembanding::where('created_at','>=',Carbon::now()->subDays(3))->count();
        return [
            Stat::make('Total Data Pembanding',Pembanding::count())
                ->icon('heroicon-m-map')
                ->description("Jumlah data yang ditambahkan dalam 3 hari terakhir: ".$lasts3Days),
            Stat::make('Total User', User::count())->icon('heroicon-m-user-group'),
        ];
    }
}
