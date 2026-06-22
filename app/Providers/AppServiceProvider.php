<?php

namespace App\Providers;

use App\Models\Pembanding;
use App\Policies\ActivityPolicy;
use App\Policies\PembandingPolicy;
use App\Support\AdminAccess;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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
        Gate::policy(Activity::class, ActivityPolicy::class);
        Gate::policy(Pembanding::class, PembandingPolicy::class);
        Gate::define('exportPembanding', fn ($user): bool => $user->can('export_data::pembanding'));
        Gate::define(
            'admin.permission',
            fn ($user, string|array $permissions): bool => AdminAccess::can($user, $permissions)
        );

        RateLimiter::for('api-write', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });
    }
}
