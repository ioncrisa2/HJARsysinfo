<?php

namespace App\Providers;

use App\Models\Pembanding;
use App\Policies\ActivityPolicy;
use App\Policies\PembandingPolicy;
use App\Support\AppAccess;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityRequirement;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
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
            'app.permission',
            fn ($user, string|array $permissions): bool => AppAccess::can($user, $permissions)
        );

        RateLimiter::for('api-write', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        Scramble::afterOpenApiGenerated(function (OpenApi $openApi): void {
            $openApi->components->addSecurityScheme(
                'sessionCookie',
                SecurityScheme::apiKey('cookie', (string) config('session.cookie'))
                    ->as('sessionCookie')
                    ->setDescription('Laravel Sanctum first-party SPA session cookie. Obtain CSRF cookies first via /sanctum/csrf-cookie.')
            );

            foreach ($openApi->paths as $path) {
                if ($path->path === 'v1/auth/session' && isset($path->operations['delete'])) {
                    $path->operations['delete']->security = [new SecurityRequirement('sessionCookie')];
                }

                if ($path->path === 'v1/auth/me' && isset($path->operations['get'])) {
                    $path->operations['get']->security = [
                        new SecurityRequirement('sessionCookie'),
                        new SecurityRequirement('http'),
                    ];
                }
            }
        });
    }
}
