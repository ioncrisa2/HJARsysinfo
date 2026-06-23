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
use App\Http\Controllers\Auth\DataContributorRegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::get('/register-data-contributor/submitted', [DataContributorRegistrationController::class, 'submitted'])
    ->name('data-contributor-registration.submitted');
Route::get('/register-data-contributor/{token}', [DataContributorRegistrationController::class, 'show'])
    ->name('data-contributor-registration.show');
Route::post('/register-data-contributor/{token}', [DataContributorRegistrationController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('data-contributor-registration.store');

Route::middleware('auth')->group(function () {
    Route::get('/home', DashboardController::class)
        ->middleware(['app.user', 'permission:view_map|view_any_data::pembanding'])
        ->name('home.dashboard');
    Route::get('/home/pembanding/export', [PembandingExportController::class, 'byFilter'])
        ->middleware(['app.user', 'permission:export_data::pembanding'])
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
        ->middleware(['app.user', 'permission:update_data::pembanding|update_own_data::pembanding'])
        ->name('home.pembanding.edit');
    Route::put('/home/pembanding/{pembanding}', [PembandingController::class, 'update'])
        ->middleware(['app.user', 'permission:update_data::pembanding|update_own_data::pembanding'])
        ->name('home.pembanding.update');
    // Master Data unified page
    Route::get('/home/master-data', MasterDataPageController::class)
        ->middleware(['app.user', 'permission:view_master_data|view_geo_data'])
        ->name('home.master-data');

    // Dictionaries API
    Route::prefix('/home/master-data/dictionaries')->middleware('app.user')->group(function () {
        Route::get('{type}', [DictionaryApiController::class, 'index'])
            ->middleware('permission:view_master_data');
        Route::post('{type}', [DictionaryApiController::class, 'store'])
            ->middleware('permission:create_master_data');
        Route::post('{type}/reorder', [DictionaryApiController::class, 'reorder'])
            ->middleware('permission:reorder_master_data');
        Route::put('{type}/{id}', [DictionaryApiController::class, 'update'])
            ->middleware('permission:update_master_data')
            ->whereNumber('id');
        Route::delete('{type}/{id}', [DictionaryApiController::class, 'destroy'])
            ->middleware('permission:delete_master_data')
            ->whereNumber('id');
    });

    // Location API
    Route::prefix('/home/master-data/locations')->middleware('app.user')->group(function () {
        Route::get('provinces', [LocationApiController::class, 'provinces'])->middleware('permission:view_geo_data');
        Route::post('provinces', [LocationApiController::class, 'storeProvince'])->middleware('permission:create_geo_data');
        Route::put('provinces/{province}', [LocationApiController::class, 'updateProvince'])->middleware('permission:update_geo_data');
        Route::delete('provinces/{province}', [LocationApiController::class, 'deleteProvince'])->middleware('permission:delete_geo_data');

        Route::get('regencies', [LocationApiController::class, 'regencies'])->middleware('permission:view_geo_data');
        Route::post('regencies', [LocationApiController::class, 'storeRegency'])->middleware('permission:create_geo_data');
        Route::put('regencies/{regency}', [LocationApiController::class, 'updateRegency'])->middleware('permission:update_geo_data');
        Route::delete('regencies/{regency}', [LocationApiController::class, 'deleteRegency'])->middleware('permission:delete_geo_data');

        Route::get('districts', [LocationApiController::class, 'districts'])->middleware('permission:view_geo_data');
        Route::post('districts', [LocationApiController::class, 'storeDistrict'])->middleware('permission:create_geo_data');
        Route::put('districts/{district}', [LocationApiController::class, 'updateDistrict'])->middleware('permission:update_geo_data');
        Route::delete('districts/{district}', [LocationApiController::class, 'deleteDistrict'])->middleware('permission:delete_geo_data');

        Route::get('villages', [LocationApiController::class, 'villages'])->middleware('permission:view_geo_data');
        Route::post('villages', [LocationApiController::class, 'storeVillage'])->middleware('permission:create_geo_data');
        Route::put('villages/{village}', [LocationApiController::class, 'updateVillage'])->middleware('permission:update_geo_data');
        Route::delete('villages/{village}', [LocationApiController::class, 'deleteVillage'])->middleware('permission:delete_geo_data');
    });

    Route::get('/profile', [ProfileController::class, 'show'])
        ->middleware(['auth'])
        ->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])
        ->middleware(['auth'])
        ->name('profile.update');

    // Dependent dropdown lookups for frontend form
    Route::get('/home/lookups/regencies', [GeoLookupController::class, 'regencies'])
        ->middleware(['app.user', 'permission:view_any_data::pembanding|create_data::pembanding|update_data::pembanding|update_own_data::pembanding']);
    Route::get('/home/lookups/districts', [GeoLookupController::class, 'districts'])
        ->middleware(['app.user', 'permission:view_any_data::pembanding|create_data::pembanding|update_data::pembanding|update_own_data::pembanding']);
    Route::get('/home/lookups/villages', [GeoLookupController::class, 'villages'])
        ->middleware(['app.user', 'permission:view_any_data::pembanding|create_data::pembanding|update_data::pembanding|update_own_data::pembanding']);
    // Admin Panel (Inertia/Vue migration)
    Route::prefix('admin')
        ->name('admin.')
        ->middleware(['app.user', 'permission:can_access_admin'])
        ->group(function () {
            Route::get('/', \App\Http\Controllers\Admin\DashboardController::class)
                ->middleware('permission:view_admin_dashboard')
                ->name('dashboard');

            Route::get('access-control', [\App\Http\Controllers\Admin\AccessControlController::class, 'index'])
                ->middleware('permission:view_access_control')
                ->name('access-control.index');
            Route::post('access-control/roles', [\App\Http\Controllers\Admin\AccessControlController::class, 'storeRole'])
                ->middleware('permission:create_role')
                ->name('access-control.roles.store');
            Route::put('access-control/roles/{role}', [\App\Http\Controllers\Admin\AccessControlController::class, 'updateRole'])
                ->middleware('permission:update_role')
                ->name('access-control.roles.update');
            Route::delete('access-control/roles/{role}', [\App\Http\Controllers\Admin\AccessControlController::class, 'destroyRole'])
                ->middleware('permission:delete_role')
                ->name('access-control.roles.destroy');
            Route::post('access-control/permissions', [\App\Http\Controllers\Admin\AccessControlController::class, 'storePermission'])
                ->middleware('permission:create_permission')
                ->name('access-control.permissions.store');
            Route::delete('access-control/permissions/{permission}', [\App\Http\Controllers\Admin\AccessControlController::class, 'destroyPermission'])
                ->middleware('permission:delete_permission')
                ->name('access-control.permissions.destroy');
            
            Route::patch('users/{user}/toggle-status', [\App\Http\Controllers\Admin\UserController::class, 'toggleStatus'])
                ->middleware('permission:update_user')
                ->name('users.toggle-status');
            Route::post('users/bulk-delete', [\App\Http\Controllers\Admin\UserController::class, 'bulkDelete'])
                ->middleware('permission:delete_any_user')
                ->name('users.bulk-delete');
            Route::resource('users', \App\Http\Controllers\Admin\UserController::class)
                ->except(['show'])
                ->middlewareFor('index', 'permission:view_any_user')
                ->middlewareFor(['create', 'store'], 'permission:create_user')
                ->middlewareFor(['edit', 'update'], 'permission:update_user')
                ->middlewareFor('destroy', 'permission:delete_user');

            Route::get('data-contributor-invitations', [\App\Http\Controllers\Admin\DataContributorInvitationController::class, 'index'])
                ->middleware('permission:manage_data_contributor_invitations')
                ->name('data-contributor-invitations.index');
            Route::post('data-contributor-invitations', [\App\Http\Controllers\Admin\DataContributorInvitationController::class, 'store'])
                ->middleware('permission:manage_data_contributor_invitations')
                ->name('data-contributor-invitations.store');
            Route::delete('data-contributor-invitations/{invite}', [\App\Http\Controllers\Admin\DataContributorInvitationController::class, 'destroy'])
                ->middleware('permission:manage_data_contributor_invitations')
                ->name('data-contributor-invitations.destroy');
            Route::post('data-contributor-registration-requests/{registrationRequest}/accept', [\App\Http\Controllers\Admin\DataContributorInvitationController::class, 'accept'])
                ->middleware('permission:manage_data_contributor_invitations')
                ->name('data-contributor-registration-requests.accept');
            Route::post('data-contributor-registration-requests/{registrationRequest}/reject', [\App\Http\Controllers\Admin\DataContributorInvitationController::class, 'reject'])
                ->middleware('permission:manage_data_contributor_invitations')
                ->name('data-contributor-registration-requests.reject');

            Route::get('moderation', [\App\Http\Controllers\Admin\ModerationController::class, 'index'])
                ->middleware('permission:view_moderation')
                ->name('moderation.index');
            Route::post('moderation/approve/{request}', [\App\Http\Controllers\Admin\ModerationController::class, 'approve'])
                ->middleware('permission:approve_delete_request')
                ->name('moderation.approve');
            Route::post('moderation/reject/{request}', [\App\Http\Controllers\Admin\ModerationController::class, 'reject'])
                ->middleware('permission:reject_delete_request')
                ->name('moderation.reject');
            Route::post('moderation/restore/{id}', [\App\Http\Controllers\Admin\ModerationController::class, 'restore'])
                ->middleware('permission:restore_data::pembanding')
                ->name('moderation.restore');
            Route::delete('moderation/force-delete/{id}', [\App\Http\Controllers\Admin\ModerationController::class, 'forceDelete'])
                ->middleware('permission:force_delete_data::pembanding')
                ->name('moderation.force-delete');

            Route::get('pembanding/{pembanding}/history', [\App\Http\Controllers\Admin\DataPembandingController::class, 'history'])
                ->middleware('permission:view_data::pembanding|view_any_data::pembanding')
                ->name('pembanding.history');
            Route::resource('pembanding', \App\Http\Controllers\Admin\DataPembandingController::class)
                ->middlewareFor('index', 'permission:view_any_data::pembanding')
                ->middlewareFor(['create', 'store'], 'permission:create_data::pembanding')
                ->middlewareFor('show', 'permission:view_data::pembanding|view_any_data::pembanding')
                ->middlewareFor(['edit', 'update'], 'permission:update_data::pembanding')
                ->middlewareFor('destroy', 'permission:delete_data::pembanding');

            // Geo Data Routes
            Route::get('geo/lookups/regencies', [\App\Http\Controllers\Admin\GeoDataController::class, 'regencies'])->middleware('permission:view_geo_data')->name('geo.lookups.regencies');
            Route::get('geo/lookups/districts', [\App\Http\Controllers\Admin\GeoDataController::class, 'districts'])->middleware('permission:view_geo_data')->name('geo.lookups.districts');
            Route::get('geo/{resource?}', [\App\Http\Controllers\Admin\GeoDataController::class, 'index'])->middleware('permission:view_geo_data')->name('geo.show');
            Route::post('geo/{resource}', [\App\Http\Controllers\Admin\GeoDataController::class, 'store'])->middleware('permission:create_geo_data')->name('geo.store');
            Route::put('geo/{resource}/{id}', [\App\Http\Controllers\Admin\GeoDataController::class, 'update'])->middleware('permission:update_geo_data')->name('geo.update');
            Route::delete('geo/{resource}/{id}', [\App\Http\Controllers\Admin\GeoDataController::class, 'destroy'])->middleware('permission:delete_geo_data')->name('geo.destroy');

            // Export Routes
            Route::get('export', [\App\Http\Controllers\Admin\ExportController::class, 'index'])->middleware('permission:view_export')->name('export.index');
            Route::get('export/download', [\App\Http\Controllers\Admin\ExportController::class, 'download'])->middleware('permission:export_data::pembanding')->name('export.download');

            // Backup Routes
            Route::get('backup', [\App\Http\Controllers\Admin\BackupController::class, 'index'])->middleware('permission:view_backup')->name('backup.index');
            Route::post('backup/database', [\App\Http\Controllers\Admin\BackupController::class, 'database'])->middleware('permission:create_database_backup')->name('backup.database');
            Route::post('backup/uploads', [\App\Http\Controllers\Admin\BackupController::class, 'uploads'])->middleware('permission:create_uploads_backup')->name('backup.uploads');

            // Search Route
            Route::get('search', \App\Http\Controllers\Admin\SearchController::class)->middleware('permission:view_admin_search')->name('search.index');

            // Profile Routes
            Route::get('profile', [\App\Http\Controllers\Admin\ProfileController::class, 'show'])->name('profile.show');
            Route::put('profile', [\App\Http\Controllers\Admin\ProfileController::class, 'updateProfile'])->name('profile.update');
            Route::put('profile/password', [\App\Http\Controllers\Admin\ProfileController::class, 'updatePassword'])->name('profile.password');

            // Settings Routes
            Route::get('settings', [\App\Http\Controllers\Admin\SettingController::class, 'index'])->middleware('permission:view_settings')->name('settings.index');
            Route::post('settings', [\App\Http\Controllers\Admin\SettingController::class, 'update'])->middleware('permission:update_settings')->name('settings.update');
            Route::post('settings/clear-cache', [\App\Http\Controllers\Admin\SettingController::class, 'clearCache'])->middleware('permission:clear_cache')->name('settings.clear-cache');

            // Activity Logs Route
            Route::get('activity-logs', [\App\Http\Controllers\Admin\ActivityLogController::class, 'index'])->middleware('permission:view_activity_log')->name('activity-logs.index');
            Route::get('activity-logs/{id}', [\App\Http\Controllers\Admin\ActivityLogController::class, 'show'])->middleware('permission:view_activity_log')->name('activity-logs.show');

            // Master Data Routes
            Route::post('master-data/{resource}/reorder', [\App\Http\Controllers\Admin\MasterDataController::class, 'reorder'])->middleware('permission:reorder_master_data')->name('master-data.reorder');
            Route::post('master-data/{resource}/bulk-delete', [\App\Http\Controllers\Admin\MasterDataController::class, 'bulkDestroy'])->middleware('permission:delete_any_master_data')->name('master-data.bulk-delete');
            Route::patch('master-data/{resource}/{id}/toggle-status', [\App\Http\Controllers\Admin\MasterDataController::class, 'toggleStatus'])->middleware('permission:update_master_data_status')->name('master-data.toggle-status');
            Route::get('master-data/{resource?}', [\App\Http\Controllers\Admin\MasterDataController::class, 'index'])->middleware('permission:view_master_data')->name('master-data.show');
            Route::post('master-data/{resource}', [\App\Http\Controllers\Admin\MasterDataController::class, 'store'])->middleware('permission:create_master_data')->name('master-data.store');
            Route::put('master-data/{resource}/{id}', [\App\Http\Controllers\Admin\MasterDataController::class, 'update'])->middleware('permission:update_master_data')->name('master-data.update');
            Route::delete('master-data/{resource}/{id}', [\App\Http\Controllers\Admin\MasterDataController::class, 'destroy'])->middleware('permission:delete_master_data')->name('master-data.destroy');
        });

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
