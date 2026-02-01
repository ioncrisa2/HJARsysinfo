<?php

namespace App\Providers;

use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\URL;
use App\Policies\ActivityPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;

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
        // URL::forceScheme('https');
        Infolist::$defaultNumberLocale = 'id';
        Gate::policy(Activity::class, ActivityPolicy::class);
    }
}
