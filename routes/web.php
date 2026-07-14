<?php

use App\Http\Controllers\App\AccessControlController;
use App\Http\Controllers\App\ActivityLogController;
use App\Http\Controllers\App\AppNotificationController;
use App\Http\Controllers\App\BackupController;
use App\Http\Controllers\App\BulkExcelImportController;
use App\Http\Controllers\App\DashboardController;
use App\Http\Controllers\App\DataContributorInvitationController;
use App\Http\Controllers\App\DictionaryApiController;
use App\Http\Controllers\App\ExportController;
use App\Http\Controllers\App\GeoDataController;
use App\Http\Controllers\App\GeoLookupController;
use App\Http\Controllers\App\MasterDataPageController;
use App\Http\Controllers\App\ModerationController;
use App\Http\Controllers\App\PembandingController;
use App\Http\Controllers\App\PembandingDuplicateReviewController;
use App\Http\Controllers\App\PembandingExportController;
use App\Http\Controllers\App\ProfileController;
use App\Http\Controllers\App\SearchController;
use App\Http\Controllers\App\SettingController;
use App\Http\Controllers\App\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\DataContributorRegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));

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

Route::middleware(['auth', 'app.user'])
    ->prefix('app')
    ->name('app.')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('pembanding/export', [PembandingExportController::class, 'byFilter'])
            ->middleware('permission:export_data::pembanding')
            ->name('pembanding.export');
        Route::get('pembanding', [PembandingController::class, 'index'])
            ->middleware('permission:view_any_data::pembanding')
            ->name('pembanding.index');
        Route::get('pembanding/create', [PembandingController::class, 'create'])
            ->middleware('permission:create_data::pembanding')
            ->name('pembanding.create');
        Route::post('pembanding', [PembandingController::class, 'store'])
            ->middleware('permission:create_data::pembanding')
            ->name('pembanding.store');
        Route::get('pembanding/duplicate-reviews/{submission}', [PembandingDuplicateReviewController::class, 'show'])
            ->middleware('permission:create_data::pembanding')
            ->name('pembanding.duplicate-reviews.show');
        Route::get('pembanding/duplicate-reviews/{submission}/image', [PembandingDuplicateReviewController::class, 'image'])
            ->middleware('permission:create_data::pembanding')
            ->name('pembanding.duplicate-reviews.image');
        Route::post('pembanding/duplicate-reviews/{submission}/use-existing/{pembanding}', [PembandingDuplicateReviewController::class, 'useExisting'])
            ->middleware('permission:create_data::pembanding')
            ->name('pembanding.duplicate-reviews.use-existing');
        Route::put('pembanding/duplicate-reviews/{submission}/replace/{pembanding}', [PembandingDuplicateReviewController::class, 'replace'])
            ->middleware('permission:create_data::pembanding')
            ->name('pembanding.duplicate-reviews.replace');
        Route::get('pembanding/{pembanding}/history', [PembandingController::class, 'history'])
            ->middleware('permission:view_any_data::pembanding')
            ->name('pembanding.history');
        Route::post('pembanding/{pembanding}/delete-request', [PembandingController::class, 'requestDelete'])
            ->middleware('permission:view_any_data::pembanding')
            ->name('pembanding.delete-request');
        Route::get('pembanding/{pembanding}', [PembandingController::class, 'show'])
            ->middleware('permission:view_any_data::pembanding')
            ->name('pembanding.show');
        Route::get('pembanding/{pembanding}/edit', [PembandingController::class, 'edit'])
            ->middleware('permission:update_data::pembanding|update_own_data::pembanding')
            ->name('pembanding.edit');
        Route::put('pembanding/{pembanding}', [PembandingController::class, 'update'])
            ->middleware('permission:update_data::pembanding|update_own_data::pembanding')
            ->name('pembanding.update');
        Route::delete('pembanding/{pembanding}', [PembandingController::class, 'destroy'])
            ->middleware('permission:delete_data::pembanding')
            ->name('pembanding.destroy');

        Route::get('pembanding-imports', [BulkExcelImportController::class, 'index'])
            ->middleware('permission:bulk_import_data::pembanding')
            ->name('bulk-excel-imports.index');
        Route::post('pembanding-imports', [BulkExcelImportController::class, 'store'])
            ->middleware(['permission:bulk_import_data::pembanding', 'throttle:10,1'])
            ->name('bulk-excel-imports.store');
        Route::get('pembanding-imports/{batch}', [BulkExcelImportController::class, 'show'])
            ->middleware('permission:bulk_import_data::pembanding')
            ->name('bulk-excel-imports.show');
        Route::patch('pembanding-imports/{batch}/selection', [BulkExcelImportController::class, 'selection'])
            ->middleware('permission:bulk_import_data::pembanding')
            ->name('bulk-excel-imports.selection');
        Route::patch('pembanding-imports/{batch}/bulk-apply', [BulkExcelImportController::class, 'bulkApply'])
            ->middleware('permission:bulk_import_data::pembanding')
            ->name('bulk-excel-imports.bulk-apply');
        Route::post('pembanding-imports/{batch}/finalize', [BulkExcelImportController::class, 'finalize'])
            ->middleware(['permission:bulk_import_data::pembanding', 'throttle:5,1'])
            ->name('bulk-excel-imports.finalize');
        Route::get('pembanding-imports/{batch}/rows/{row}/edit', [BulkExcelImportController::class, 'edit'])
            ->middleware('permission:bulk_import_data::pembanding')
            ->name('bulk-excel-imports.rows.edit');
        Route::put('pembanding-imports/{batch}/rows/{row}', [BulkExcelImportController::class, 'update'])
            ->middleware('permission:bulk_import_data::pembanding')
            ->name('bulk-excel-imports.rows.update');
        Route::get('pembanding-imports/{batch}/rows/{row}/image', [BulkExcelImportController::class, 'image'])
            ->middleware('permission:bulk_import_data::pembanding')
            ->name('bulk-excel-imports.rows.image');
        Route::post('pembanding-imports/{batch}/rows/{row}/retry', [BulkExcelImportController::class, 'retry'])
            ->middleware(['permission:bulk_import_data::pembanding', 'throttle:10,1'])
            ->name('bulk-excel-imports.rows.retry');

        Route::get('master-data', [MasterDataPageController::class, 'index'])
            ->middleware('permission:view_master_data')
            ->name('master-data.index');

        Route::prefix('master-data/dictionaries')->group(function () {
            Route::get('{type}', [DictionaryApiController::class, 'index'])->middleware('permission:view_master_data')->name('dictionaries.index');
            Route::post('{type}', [DictionaryApiController::class, 'store'])->middleware('permission:create_master_data')->name('dictionaries.store');
            Route::post('{type}/reorder', [DictionaryApiController::class, 'reorder'])->middleware('permission:reorder_master_data')->name('dictionaries.reorder');
            Route::patch('{type}/{id}/status', [DictionaryApiController::class, 'updateStatus'])->middleware('permission:update_master_data_status')->whereNumber('id')->name('dictionaries.status');
            Route::put('{type}/{id}', [DictionaryApiController::class, 'update'])->middleware('permission:update_master_data')->whereNumber('id')->name('dictionaries.update');
            Route::delete('{type}/{id}', [DictionaryApiController::class, 'destroy'])->middleware('permission:delete_master_data|delete_any_master_data')->whereNumber('id')->name('dictionaries.destroy');
        });

        Route::get('master-data/{type}', [MasterDataPageController::class, 'show'])
            ->middleware('permission:view_master_data')
            ->name('master-data.show');

        Route::get('lookups/regencies', [GeoLookupController::class, 'regencies'])
            ->middleware('permission:view_any_data::pembanding|create_data::pembanding|update_data::pembanding|update_own_data::pembanding|bulk_import_data::pembanding')->name('lookups.regencies');
        Route::get('lookups/districts', [GeoLookupController::class, 'districts'])
            ->middleware('permission:view_any_data::pembanding|create_data::pembanding|update_data::pembanding|update_own_data::pembanding|bulk_import_data::pembanding')->name('lookups.districts');
        Route::get('lookups/villages', [GeoLookupController::class, 'villages'])
            ->middleware('permission:view_any_data::pembanding|create_data::pembanding|update_data::pembanding|update_own_data::pembanding|bulk_import_data::pembanding')->name('lookups.villages');

        Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::get('access-control', [AccessControlController::class, 'index'])->middleware('permission:view_access_control')->name('access-control.index');
        Route::post('access-control/roles', [AccessControlController::class, 'storeRole'])->middleware('permission:create_role')->name('access-control.roles.store');
        Route::put('access-control/roles/{role}', [AccessControlController::class, 'updateRole'])->middleware('permission:update_role')->name('access-control.roles.update');
        Route::delete('access-control/roles/{role}', [AccessControlController::class, 'destroyRole'])->middleware('permission:delete_role')->name('access-control.roles.destroy');
        Route::post('access-control/permissions', [AccessControlController::class, 'storePermission'])->middleware('permission:create_permission')->name('access-control.permissions.store');
        Route::delete('access-control/permissions/{permission}', [AccessControlController::class, 'destroyPermission'])->middleware('permission:delete_permission')->name('access-control.permissions.destroy');

        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->middleware('permission:update_user')->name('users.toggle-status');
        Route::post('users/bulk-delete', [UserController::class, 'bulkDelete'])->middleware('permission:delete_any_user')->name('users.bulk-delete');
        Route::resource('users', UserController::class)
            ->except(['show'])
            ->middlewareFor('index', 'permission:view_any_user')
            ->middlewareFor(['create', 'store'], 'permission:create_user')
            ->middlewareFor(['edit', 'update'], 'permission:update_user')
            ->middlewareFor('destroy', 'permission:delete_user');

        Route::get('data-contributor-invitations', [DataContributorInvitationController::class, 'index'])->middleware('permission:manage_data_contributor_invitations')->name('data-contributor-invitations.index');
        Route::post('data-contributor-invitations', [DataContributorInvitationController::class, 'store'])->middleware('permission:manage_data_contributor_invitations')->name('data-contributor-invitations.store');
        Route::delete('data-contributor-invitations/{invite}', [DataContributorInvitationController::class, 'destroy'])->middleware('permission:manage_data_contributor_invitations')->name('data-contributor-invitations.destroy');
        Route::post('data-contributor-registration-requests/{registrationRequest}/accept', [DataContributorInvitationController::class, 'accept'])->middleware('permission:manage_data_contributor_invitations')->name('data-contributor-registration-requests.accept');
        Route::post('data-contributor-registration-requests/{registrationRequest}/reject', [DataContributorInvitationController::class, 'reject'])->middleware('permission:manage_data_contributor_invitations')->name('data-contributor-registration-requests.reject');

        Route::get('moderation', [ModerationController::class, 'index'])->middleware('permission:view_moderation')->name('moderation.index');
        Route::post('moderation/approve/{request}', [ModerationController::class, 'approve'])->middleware('permission:approve_delete_request')->name('moderation.approve');
        Route::post('moderation/reject/{request}', [ModerationController::class, 'reject'])->middleware('permission:reject_delete_request')->name('moderation.reject');
        Route::post('moderation/restore/{id}', [ModerationController::class, 'restore'])->middleware('permission:restore_data::pembanding')->name('moderation.restore');
        Route::delete('moderation/force-delete/{id}', [ModerationController::class, 'forceDelete'])->middleware('permission:force_delete_data::pembanding')->name('moderation.force-delete');

        Route::get('geo/lookups/regencies', [GeoDataController::class, 'regencies'])->middleware('permission:view_geo_data')->name('geo.lookups.regencies');
        Route::get('geo/lookups/districts', [GeoDataController::class, 'districts'])->middleware('permission:view_geo_data')->name('geo.lookups.districts');
        Route::get('geo/{resource?}', [GeoDataController::class, 'index'])->middleware('permission:view_geo_data')->name('geo.show');
        Route::post('geo/{resource}', [GeoDataController::class, 'store'])->middleware('permission:create_geo_data')->name('geo.store');
        Route::put('geo/{resource}/{id}', [GeoDataController::class, 'update'])->middleware('permission:update_geo_data')->name('geo.update');
        Route::delete('geo/{resource}/{id}', [GeoDataController::class, 'destroy'])->middleware('permission:delete_geo_data')->name('geo.destroy');

        Route::get('export', [ExportController::class, 'index'])->middleware('permission:view_export')->name('export.index');
        Route::get('export/download', [ExportController::class, 'download'])->middleware('permission:export_data::pembanding')->name('export.download');
        Route::post('export/preview', [ExportController::class, 'preview'])->middleware('permission:export_data::pembanding')->name('export.preview');
        Route::post('export/runs', [ExportController::class, 'store'])->middleware('permission:export_data::pembanding')->name('export.runs.store');
        Route::get('export/runs/{exportRun}', [ExportController::class, 'status'])->middleware('permission:export_data::pembanding')->name('export.runs.status');
        Route::get('export/runs/{exportRun}/download', [ExportController::class, 'downloadRun'])->middleware('permission:export_data::pembanding')->name('export.runs.download');
        Route::post('export/runs/{exportRun}/retry', [ExportController::class, 'retry'])->middleware('permission:export_data::pembanding')->name('export.runs.retry');
        Route::get('backup', [BackupController::class, 'index'])->middleware('permission:view_backup')->name('backup.index');
        Route::post('backup/database', [BackupController::class, 'database'])->middleware('permission:create_database_backup')->name('backup.database');
        Route::post('backup/uploads', [BackupController::class, 'uploads'])->middleware('permission:create_uploads_backup')->name('backup.uploads');
        Route::get('search', SearchController::class)->middleware('permission:view_search')->name('search.index');
        Route::get('settings', [SettingController::class, 'index'])->middleware('permission:view_settings')->name('settings.index');
        Route::post('settings', [SettingController::class, 'update'])->middleware('permission:update_settings')->name('settings.update');
        Route::post('settings/clear-cache', [SettingController::class, 'clearCache'])->middleware('permission:clear_cache')->name('settings.clear-cache');
        Route::get('activity-logs', [ActivityLogController::class, 'index'])->middleware('permission:view_activity_log')->name('activity-logs.index');
        Route::get('activity-logs/{id}', [ActivityLogController::class, 'show'])->middleware('permission:view_activity_log')->name('activity-logs.show');
        Route::post('notifications/read-all', [AppNotificationController::class, 'readAll'])->name('notifications.read-all');
    });

Route::middleware('auth')->group(function () {
    Route::get('/profile', fn () => redirect()->route('app.profile.show'));
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
