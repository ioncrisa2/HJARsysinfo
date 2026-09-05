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

            $aliases = [
                'auth/me' => 'v1/auth/me',
                'auth/profile' => 'v1/auth/profile',
                'auth/profile/password' => 'v1/auth/profile/password',
                'v1/pembanding-submissions/{submission}/resolve' => 'v1/pembanding-submissions/{submission}/resolution',
            ];

            foreach ($openApi->paths as $path) {
                foreach ($path->operations as $method => $operation) {
                    // MiddlewareAuthSecurityStrategy marks public operations with [].
                    // Protected operations inherit the default security (null).
                    if ($operation->security === null) {
                        $operation->security = [
                            new SecurityRequirement('sessionCookie'),
                            new SecurityRequirement('http'),
                        ];
                    }

                    if ($path->path === 'v1/auth/session' && $method === 'delete') {
                        $operation->security = [new SecurityRequirement('sessionCookie')];
                    } elseif ($path->path === 'auth/logout') {
                        $operation->security = [new SecurityRequirement('http')];
                    }

                    if (isset($aliases[$path->path])) {
                        $operation->deprecated = true;
                        $operation->operationId .= '.legacy';
                        $operation->description .= "\n\nAlias kompatibilitas. Gunakan `/api/{$aliases[$path->path]}` untuk integrasi baru. URL lama tetap tersedia sampai migrasi klien selesai.";
                    }
                }
            }
        });
    }
}
