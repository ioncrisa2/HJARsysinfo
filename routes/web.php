<?php

use App\Http\Controllers\App\DashboardController;
use App\Http\Controllers\App\GeoLookupController;
use App\Http\Controllers\App\MasterDataPageController;
use App\Http\Controllers\App\DictionaryApiController;
use App\Http\Controllers\App\LocationApiController;
use App\Http\Controllers\App\PembandingExportController;
use App\Http\Controllers\App\PembandingController;
use App\Http\Controllers\App\ProfileController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/home', DashboardController::class)
        ->middleware(['app.user', 'permission:view_any_data::pembanding'])
        ->name('home.dashboard');
    Route::get('/home/pembanding/export', [PembandingExportController::class, 'byFilter'])
        ->middleware(['app.user', 'permission:view_any_data::pembanding'])
        ->name('home.pembanding.export');
    Route::get('/home/pembanding', [PembandingController::class, 'index'])
        ->middleware(['app.user', 'permission:view_any_data::pembanding'])
        ->name('home.pembanding.index');
    Route::get('/home/pembanding/create', [PembandingController::class, 'create'])
        ->middleware(['app.user', 'permission:create_data::pembanding'])
        ->name('home.pembanding.create');
    Route::post('/home/pembanding', [PembandingController::class, 'store'])
        ->middleware(['app.user', 'permission:create_data::pembanding'])
        ->name('home.pembanding.store');
    Route::get('/home/pembanding/{pembanding}/history', [PembandingController::class, 'history'])
        ->middleware(['app.user', 'permission:view_any_data::pembanding'])
        ->name('home.pembanding.history');
    Route::post('/home/pembanding/{pembanding}/delete-request', [PembandingController::class, 'requestDelete'])
        ->middleware(['app.user', 'permission:view_any_data::pembanding'])
        ->name('home.pembanding.delete-request');
    Route::get('/home/pembanding/{pembanding}', [PembandingController::class, 'show'])
        ->middleware(['app.user', 'permission:view_any_data::pembanding'])
        ->name('home.pembanding.show');
    Route::get('/home/pembanding/{pembanding}/edit', [PembandingController::class, 'edit'])
        ->middleware(['app.user', 'permission:update_data::pembanding'])
        ->name('home.pembanding.edit');
    Route::put('/home/pembanding/{pembanding}', [PembandingController::class, 'update'])
        ->middleware(['app.user', 'permission:update_data::pembanding'])
        ->name('home.pembanding.update');
    // Master Data unified page
    Route::get('/home/master-data', MasterDataPageController::class)
        ->middleware(['app.user', 'role_or_permission:surveyor|manage_master_data'])
        ->name('home.master-data');

    // Dictionaries API
    Route::prefix('/home/master-data/dictionaries')->middleware(['app.user', 'role_or_permission:surveyor|manage_master_data'])->group(function () {
        Route::get('{type}', [DictionaryApiController::class, 'index']);
        Route::post('{type}', [DictionaryApiController::class, 'store']);
        Route::post('{type}/reorder', [DictionaryApiController::class, 'reorder']);
        Route::put('{type}/{id}', [DictionaryApiController::class, 'update'])->whereNumber('id');
        Route::delete('{type}/{id}', [DictionaryApiController::class, 'destroy'])->whereNumber('id');
    });

    // Location API
    Route::prefix('/home/master-data/locations')->middleware(['app.user', 'role_or_permission:surveyor|manage_master_data'])->group(function () {
        Route::get('provinces', [LocationApiController::class, 'provinces']);
        Route::post('provinces', [LocationApiController::class, 'storeProvince']);
        Route::put('provinces/{province}', [LocationApiController::class, 'updateProvince']);
        Route::delete('provinces/{province}', [LocationApiController::class, 'deleteProvince']);

        Route::get('regencies', [LocationApiController::class, 'regencies']);
        Route::post('regencies', [LocationApiController::class, 'storeRegency']);
        Route::put('regencies/{regency}', [LocationApiController::class, 'updateRegency']);
        Route::delete('regencies/{regency}', [LocationApiController::class, 'deleteRegency']);

        Route::get('districts', [LocationApiController::class, 'districts']);
        Route::post('districts', [LocationApiController::class, 'storeDistrict']);
        Route::put('districts/{district}', [LocationApiController::class, 'updateDistrict']);
        Route::delete('districts/{district}', [LocationApiController::class, 'deleteDistrict']);

        Route::get('villages', [LocationApiController::class, 'villages']);
        Route::post('villages', [LocationApiController::class, 'storeVillage']);
        Route::put('villages/{village}', [LocationApiController::class, 'updateVillage']);
        Route::delete('villages/{village}', [LocationApiController::class, 'deleteVillage']);
    });

    Route::get('/profile', [ProfileController::class, 'show'])
        ->middleware(['auth'])
        ->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])
        ->middleware(['auth'])
        ->name('profile.update');

    // Dependent dropdown lookups for frontend form
    Route::get('/home/lookups/regencies', [GeoLookupController::class, 'regencies'])->middleware('app.user');
    Route::get('/home/lookups/districts', [GeoLookupController::class, 'districts'])->middleware('app.user');
    Route::get('/home/lookups/villages', [GeoLookupController::class, 'villages'])->middleware('app.user');
    // Admin Panel (Inertia/Vue migration)
    Route::prefix('admin')
        ->name('admin.')
        ->middleware(['app.user', 'role:super_admin'])
        ->group(function () {
            Route::get('/', \App\Http\Controllers\Admin\DashboardController::class)->name('dashboard');

            Route::get('access-control', [\App\Http\Controllers\Admin\AccessControlController::class, 'index'])->name('access-control.index');
            Route::post('access-control/roles', [\App\Http\Controllers\Admin\AccessControlController::class, 'storeRole'])->name('access-control.roles.store');
            Route::put('access-control/roles/{role}', [\App\Http\Controllers\Admin\AccessControlController::class, 'updateRole'])->name('access-control.roles.update');
            Route::delete('access-control/roles/{role}', [\App\Http\Controllers\Admin\AccessControlController::class, 'destroyRole'])->name('access-control.roles.destroy');
            Route::post('access-control/permissions', [\App\Http\Controllers\Admin\AccessControlController::class, 'storePermission'])->name('access-control.permissions.store');
            Route::delete('access-control/permissions/{permission}', [\App\Http\Controllers\Admin\AccessControlController::class, 'destroyPermission'])->name('access-control.permissions.destroy');
            
            Route::patch('users/{user}/toggle-status', [\App\Http\Controllers\Admin\UserController::class, 'toggleStatus'])->name('users.toggle-status');
            Route::post('users/bulk-delete', [\App\Http\Controllers\Admin\UserController::class, 'bulkDelete'])->name('users.bulk-delete');
            Route::resource('users', \App\Http\Controllers\Admin\UserController::class)
                ->except(['show']);

            Route::get('moderation', [\App\Http\Controllers\Admin\ModerationController::class, 'index'])->name('moderation.index');
            Route::post('moderation/approve/{request}', [\App\Http\Controllers\Admin\ModerationController::class, 'approve'])->name('moderation.approve');
            Route::post('moderation/reject/{request}', [\App\Http\Controllers\Admin\ModerationController::class, 'reject'])->name('moderation.reject');
            Route::post('moderation/restore/{id}', [\App\Http\Controllers\Admin\ModerationController::class, 'restore'])->name('moderation.restore');
            Route::delete('moderation/force-delete/{id}', [\App\Http\Controllers\Admin\ModerationController::class, 'forceDelete'])->name('moderation.force-delete');

            Route::resource('pembanding', \App\Http\Controllers\Admin\DataPembandingController::class);

            // Geo Data Routes
            Route::get('geo/lookups/regencies', [\App\Http\Controllers\Admin\GeoDataController::class, 'regencies'])->name('geo.lookups.regencies');
            Route::get('geo/lookups/districts', [\App\Http\Controllers\Admin\GeoDataController::class, 'districts'])->name('geo.lookups.districts');
            Route::get('geo/{resource?}', [\App\Http\Controllers\Admin\GeoDataController::class, 'index'])->name('geo.show');
            Route::post('geo/{resource}', [\App\Http\Controllers\Admin\GeoDataController::class, 'store'])->name('geo.store');
            Route::put('geo/{resource}/{id}', [\App\Http\Controllers\Admin\GeoDataController::class, 'update'])->name('geo.update');
            Route::delete('geo/{resource}/{id}', [\App\Http\Controllers\Admin\GeoDataController::class, 'destroy'])->name('geo.destroy');

            // Export Routes
            Route::get('export', [\App\Http\Controllers\Admin\ExportController::class, 'index'])->name('export.index');
            Route::get('export/download', [\App\Http\Controllers\Admin\ExportController::class, 'download'])->name('export.download');

            // Backup Routes
            Route::get('backup', [\App\Http\Controllers\Admin\BackupController::class, 'index'])->name('backup.index');
            Route::post('backup/database', [\App\Http\Controllers\Admin\BackupController::class, 'database'])->name('backup.database');
            Route::post('backup/uploads', [\App\Http\Controllers\Admin\BackupController::class, 'uploads'])->name('backup.uploads');

            // Search Route
            Route::get('search', \App\Http\Controllers\Admin\SearchController::class)->name('search.index');

            // Profile Routes
            Route::get('profile', [\App\Http\Controllers\Admin\ProfileController::class, 'show'])->name('profile.show');
            Route::put('profile', [\App\Http\Controllers\Admin\ProfileController::class, 'updateProfile'])->name('profile.update');
            Route::put('profile/password', [\App\Http\Controllers\Admin\ProfileController::class, 'updatePassword'])->name('profile.password');

            // Settings Routes
            Route::get('settings', [\App\Http\Controllers\Admin\SettingController::class, 'index'])->name('settings.index');
            Route::post('settings', [\App\Http\Controllers\Admin\SettingController::class, 'update'])->name('settings.update');
            Route::post('settings/clear-cache', [\App\Http\Controllers\Admin\SettingController::class, 'clearCache'])->name('settings.clear-cache');

            // Activity Logs Route
            Route::get('activity-logs', [\App\Http\Controllers\Admin\ActivityLogController::class, 'index'])->name('activity-logs.index');
            Route::get('activity-logs/{id}', [\App\Http\Controllers\Admin\ActivityLogController::class, 'show'])->name('activity-logs.show');

            // Master Data Routes
            Route::post('master-data/{resource}/reorder', [\App\Http\Controllers\Admin\MasterDataController::class, 'reorder'])->name('master-data.reorder');
            Route::post('master-data/{resource}/bulk-delete', [\App\Http\Controllers\Admin\MasterDataController::class, 'bulkDestroy'])->name('master-data.bulk-delete');
            Route::patch('master-data/{resource}/{id}/toggle-status', [\App\Http\Controllers\Admin\MasterDataController::class, 'toggleStatus'])->name('master-data.toggle-status');
            Route::get('master-data/{resource?}', [\App\Http\Controllers\Admin\MasterDataController::class, 'index'])->name('master-data.show');
            Route::post('master-data/{resource}', [\App\Http\Controllers\Admin\MasterDataController::class, 'store'])->name('master-data.store');
            Route::put('master-data/{resource}/{id}', [\App\Http\Controllers\Admin\MasterDataController::class, 'update'])->name('master-data.update');
            Route::delete('master-data/{resource}/{id}', [\App\Http\Controllers\Admin\MasterDataController::class, 'destroy'])->name('master-data.destroy');
        });

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
