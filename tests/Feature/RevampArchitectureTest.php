<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

it('keeps authenticated features under the canonical application route group', function () {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => str_starts_with($route->uri(), 'app'));

    expect($routes)->not->toBeEmpty();

    $routes->each(function ($route): void {
        expect($route->getName())->toStartWith('app.')
            ->and($route->gatherMiddleware())->toContain('auth', 'app.user');
    });
});

it('uses AppLayout for every authenticated Inertia page', function () {
    $pageRoot = resource_path('js/Pages');
    $pages = collect(File::allFiles($pageRoot))
        ->reject(fn (SplFileInfo $file): bool => str_starts_with(
            str_replace('\\', '/', $file->getRelativePathname()),
            'Auth/'
        ));

    expect($pages)->not->toBeEmpty();

    $pages->each(function (SplFileInfo $file): void {
        $contents = File::get($file->getPathname());

        expect($contents, $file->getRelativePathname())->toContain('import AppLayout');
        expect(
            str_contains($contents, '<AppLayout') || str_contains($contents, 'layout: AppLayout'),
            $file->getRelativePathname()
        )->toBeTrue();
    });
});

it('does not restore legacy panel folders layouts or frontend entrypoints', function () {
    $removedPaths = [
        resource_path('js/inertia'),
        resource_path('js/Pages/Admin'),
        resource_path('js/components/admin'),
        resource_path('js/Layouts/AdminLayout.vue'),
        resource_path('js/Layouts/UserLayout.vue'),
        resource_path('js/Layouts/TopNavLayout.vue'),
        resource_path('js/Layouts/PembandingImportLayout.vue'),
        app_path('Http/Controllers/Admin'),
        app_path('Http/Requests/Admin'),
    ];

    foreach ($removedPaths as $path) {
        expect(File::exists($path), $path)->toBeFalse();
    }

    expect(File::get(resource_path('js/app.js')))
        ->toContain('./Pages/**/*.vue')
        ->not->toContain('./inertia/');

    expect(File::get(resource_path('views/inertia.blade.php')))
        ->toContain("'resources/js/app.js'")
        ->not->toContain('resources/js/inertia/app.js');
});
