<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

it('maintains api-only architecture with no legacy frontend assets', function () {
    $removedPaths = [
        resource_path('js'),
        resource_path('css'),
        resource_path('views/inertia.blade.php'),
        resource_path('views/app.blade.php'),
        resource_path('views/welcome.blade.php'),
        base_path('vite.config.js'),
        base_path('package.json'),
        app_path('Http/Controllers/App'),
        app_path('Http/Controllers/Auth'),
    ];

    foreach ($removedPaths as $path) {
        expect(File::exists($path), "Expected {$path} to be removed")->toBeFalse();
    }

    // PDF templates preserved
    expect(File::exists(resource_path('views/exports/pembanding-pdf.blade.php')))->toBeTrue()
        ->and(File::exists(resource_path('views/exports/pembanding-summary-pdf.blade.php')))->toBeTrue();
});

it('keeps authenticated api features under the v1 route group', function () {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => str_starts_with($route->uri(), 'api/v1'));

    expect($routes)->not->toBeEmpty();

    $protectedRoutes = $routes->filter(fn ($route): bool => ! in_array($route->uri(), [
        'api/v1/auth/session',
        'api/v1/settings/public',
        'api/v1/public/data-contributor-registration/{token}',
    ]));

    $protectedRoutes->each(function ($route): void {
        $middleware = $route->gatherMiddleware();
        expect($middleware)->toContain('auth:sanctum')
            ->and($middleware)->toContain('app.user');
    });
});

it('does not register legacy panel or web app routes', function () {
    $legacyRoutes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => preg_match('#^(home|admin|app)(/|$)#', $route->uri()) === 1);

    expect($legacyRoutes)->toBeEmpty();
});
