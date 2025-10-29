<?php

namespace App\Providers;

use Filament\Infolists\Infolist;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Infolist::$defaultNumberLocale = 'id';
    }
}
