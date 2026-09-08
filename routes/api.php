<?php

use App\Http\Controllers\Api\AccessControlController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AppNotificationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BackupArtifactController;
use App\Http\Controllers\Api\BackupController;
use App\Http\Controllers\Api\BackupFileController;
use App\Http\Controllers\Api\BackupRestoreController;
use App\Http\Controllers\Api\BulkExcelImportController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DataContributorInvitationController;
use App\Http\Controllers\Api\DataContributorRegistrationController;
use App\Http\Controllers\Api\DataPembandingController;
use App\Http\Controllers\Api\DictionaryController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\GeoDataController;
use App\Http\Controllers\Api\IntegrationController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\ModerationController;
use App\Http\Controllers\Api\PembandingDuplicateReviewController;
use App\Http\Controllers\Api\PembandingMapController;
use App\Http\Controllers\Api\PublicSettingController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\UserController;
use App\Http\Middleware\ThrottleAuthAttempts;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Bearer Authentication & Legacy Profile Aliases (/api/auth/*)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware(ThrottleAuthAttempts::class);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::middleware(['auth:sanctum', 'app.user'])->group(function () {
        // Compatibility aliases; new web/mobile clients use /api/v1/auth/* for profiles.
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/profile/password', [AuthController::class, 'updatePassword']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

/*
|--------------------------------------------------------------------------
| Version 1 API Routes (/api/v1/*)
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {

    // ── Public / Unauthenticated Endpoints ────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('/session', [AuthController::class, 'sessionLogin'])->middleware(ThrottleAuthAttempts::class);
    });

    Route::get('/settings/public', [PublicSettingController::class, 'show']);

    Route::prefix('public')->group(function () {
        Route::get('/data-contributor-registration/{token}', [DataContributorRegistrationController::class, 'show']);
        Route::post('/data-contributor-registration/{token}', [DataContributorRegistrationController::class, 'store'])
            ->middleware('throttle:5,1');
    });

    // ── Authenticated Endpoints (Sanctum Session / Bearer Token) ─────────
    // Register before the legacy POST /pembandings/{id} multipart route.
    Route::post('/pembandings/similar', [DataPembandingController::class, 'similarByPayload'])
        ->middleware('consumer:pembandings:similar,view_any_data::pembanding');

    Route::middleware(['auth:sanctum', 'app.user'])->group(function () {

        // Web session and shared web/mobile profile endpoints
        Route::prefix('auth')->group(function () {
            Route::delete('/session', [AuthController::class, 'sessionLogout']);
            Route::get('/me', [AuthController::class, 'me']);
            Route::put('/profile', [AuthController::class, 'updateProfile']);
            Route::put('/profile/password', [AuthController::class, 'updatePassword']);
        });

        // Dashboard
        Route::get('/dashboard', DashboardController::class);

        // Global Search
        Route::get('/search', SearchController::class)->middleware('permission:view_search');

        // In-App Notifications
        Route::prefix('notifications')->group(function () {
            Route::get('/', [AppNotificationController::class, 'index']);
            Route::patch('/{id}/read', [AppNotificationController::class, 'read']);
            Route::post('/read-all', [AppNotificationController::class, 'readAll']);
        });

        // Dictionaries & Master Data
        Route::prefix('dictionaries')->group(function () {
            Route::get('/', [DictionaryController::class, 'definitions']);
            Route::post('/{type}', [DictionaryController::class, 'store'])->middleware('permission:create_master_data');
            Route::post('/{type}/reorder', [DictionaryController::class, 'reorder'])->middleware('permission:reorder_master_data');
            Route::put('/{type}/{id}', [DictionaryController::class, 'update'])->middleware('permission:update_master_data');
            Route::patch('/{type}/{id}/status', [DictionaryController::class, 'updateStatus'])->middleware('permission:update_master_data_status');
            Route::delete('/{type}/{id}', [DictionaryController::class, 'destroy'])->middleware('permission:delete_master_data|delete_any_master_data');
        });

        // Location Master Data & Geo Administration

        Route::prefix('geo')->group(function () {
            Route::get('/{resource?}', [GeoDataController::class, 'index'])->middleware('permission:view_geo_data');
            Route::post('/{resource}', [GeoDataController::class, 'store'])->middleware('permission:create_geo_data');
            Route::put('/{resource}/{id}', [GeoDataController::class, 'update'])->middleware('permission:update_geo_data');
            Route::delete('/{resource}/{id}', [GeoDataController::class, 'destroy'])->middleware('permission:delete_geo_data');
        });

        // Data Pembanding
        Route::prefix('pembandings')->group(function () {
            Route::get('/map', PembandingMapController::class)->middleware('permission:view_map');
            Route::get('/form-options', [DataPembandingController::class, 'formOptions'])
                ->middleware('permission:create_data::pembanding|update_data::pembanding|update_own_data::pembanding');
            Route::get('/creators', [DataPembandingController::class, 'creators'])->middleware('permission:view_any_data::pembanding');
            Route::get('/{id}/history', [DataPembandingController::class, 'history'])->middleware('permission:view_any_data::pembanding');

            Route::middleware('throttle:api-write')->group(function () {
                Route::post('/', [DataPembandingController::class, 'store'])->middleware('permission:create_data::pembanding');
                Route::post('/{id}', [DataPembandingController::class, 'update'])->middleware('permission:update_data::pembanding|update_own_data::pembanding'); // multipart workaround
                Route::put('/{id}', [DataPembandingController::class, 'update'])->middleware('permission:update_data::pembanding|update_own_data::pembanding');
                Route::patch('/{id}', [DataPembandingController::class, 'update'])->middleware('permission:update_data::pembanding|update_own_data::pembanding');
                Route::delete('/{id}', [DataPembandingController::class, 'destroy'])->middleware('permission:delete_data::pembanding');
                Route::post('/{id}/delete-request', [DataPembandingController::class, 'requestDelete'])->middleware('permission:view_any_data::pembanding');
            });
        });

        // Duplicate Review
        Route::prefix('pembanding-submissions')->group(function () {
            Route::get('/{submission}', [PembandingDuplicateReviewController::class, 'show'])->middleware('permission:create_data::pembanding');
            Route::get('/{submission}/image', [PembandingDuplicateReviewController::class, 'image'])->middleware('permission:create_data::pembanding');
            Route::post('/{submission}/resolution', [PembandingDuplicateReviewController::class, 'resolve'])->middleware('permission:create_data::pembanding');
            // Compatibility alias; /resolution is the canonical endpoint.
            Route::post('/{submission}/resolve', [PembandingDuplicateReviewController::class, 'resolve'])->middleware('permission:create_data::pembanding');
        });

        // Bulk Excel Import
        Route::prefix('pembanding-imports')->group(function () {
            Route::get('/', [BulkExcelImportController::class, 'index'])->middleware('permission:bulk_import_data::pembanding');
            Route::post('/', [BulkExcelImportController::class, 'store'])->middleware(['permission:bulk_import_data::pembanding', 'throttle:10,1']);
            Route::get('/{batch}', [BulkExcelImportController::class, 'show'])->middleware('permission:bulk_import_data::pembanding');
            Route::patch('/{batch}/selection', [BulkExcelImportController::class, 'selection'])->middleware('permission:bulk_import_data::pembanding');
            Route::patch('/{batch}/bulk-apply', [BulkExcelImportController::class, 'bulkApply'])->middleware('permission:bulk_import_data::pembanding');
            Route::post('/{batch}/finalize', [BulkExcelImportController::class, 'finalize'])->middleware(['permission:bulk_import_data::pembanding', 'throttle:5,1']);
            Route::get('/{batch}/rows/{row}', [BulkExcelImportController::class, 'editRow'])->middleware('permission:bulk_import_data::pembanding');
            Route::put('/{batch}/rows/{row}', [BulkExcelImportController::class, 'updateRow'])->middleware('permission:bulk_import_data::pembanding');
            Route::get('/{batch}/rows/{row}/image', [BulkExcelImportController::class, 'rowImage'])->middleware('permission:bulk_import_data::pembanding');
            Route::post('/{batch}/rows/{row}/retry', [BulkExcelImportController::class, 'retryRow'])->middleware(['permission:bulk_import_data::pembanding', 'throttle:10,1']);
        });

        // Users Management & Role Options
        Route::get('/roles/options', [UserController::class, 'roleOptions']);
        Route::patch('/users/{user}/status', [UserController::class, 'toggleStatus'])->middleware('permission:update_user');
        Route::post('/users/bulk-delete', [UserController::class, 'bulkDelete'])->middleware('permission:delete_any_user');
        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index'])->middleware('permission:view_any_user');
            Route::post('/', [UserController::class, 'store'])->middleware('permission:create_user');
            Route::get('/{user}', [UserController::class, 'show'])->middleware('permission:view_any_user');
            Route::put('/{user}', [UserController::class, 'update'])->middleware('permission:update_user');
            Route::delete('/{user}', [UserController::class, 'destroy'])->middleware('permission:delete_user');
        });

        // Access Control (Roles & Permissions)
        Route::prefix('roles')->group(function () {
            Route::get('/', [AccessControlController::class, 'roles'])->middleware('role_or_permission:super_admin|view_access_control');
            Route::post('/', [AccessControlController::class, 'storeRole'])->middleware('role_or_permission:super_admin|create_role');
            Route::put('/{role}', [AccessControlController::class, 'updateRole'])->middleware('role_or_permission:super_admin|update_role');
            Route::delete('/{role}', [AccessControlController::class, 'destroyRole'])->middleware('role_or_permission:super_admin|delete_role');
        });

        Route::prefix('permissions')->group(function () {
            Route::get('/', [AccessControlController::class, 'permissions'])->middleware('role_or_permission:super_admin|view_access_control');
            Route::post('/', [AccessControlController::class, 'storePermission'])->middleware('role_or_permission:super_admin|create_permission');
            Route::delete('/{permission}', [AccessControlController::class, 'destroyPermission'])->middleware('role_or_permission:super_admin|delete_permission');
        });

        // Contributor Invitations & Requests
        Route::prefix('data-contributor-invitations')->group(function () {
            Route::get('/', [DataContributorInvitationController::class, 'index'])->middleware('permission:manage_data_contributor_invitations');
            Route::post('/', [DataContributorInvitationController::class, 'store'])->middleware('permission:manage_data_contributor_invitations');
            Route::delete('/{invite}', [DataContributorInvitationController::class, 'destroy'])->middleware('permission:manage_data_contributor_invitations');
        });

        Route::prefix('data-contributor-registration-requests')->group(function () {
            Route::get('/', [DataContributorInvitationController::class, 'registrationRequests'])->middleware('permission:manage_data_contributor_invitations');
            Route::post('/{registrationRequest}/accept', [DataContributorInvitationController::class, 'accept'])->middleware('permission:manage_data_contributor_invitations');
            Route::post('/{registrationRequest}/reject', [DataContributorInvitationController::class, 'reject'])->middleware('permission:manage_data_contributor_invitations');
        });

        // Moderation & Trash
        Route::prefix('moderation')->group(function () {
            Route::get('/', [ModerationController::class, 'index'])->middleware('permission:view_moderation');
            Route::post('/delete-requests/{id}/approve', [ModerationController::class, 'approve'])->middleware('permission:approve_delete_request');
            Route::post('/delete-requests/{id}/reject', [ModerationController::class, 'reject'])->middleware('permission:reject_delete_request');
            Route::post('/pembandings/{id}/restore', [ModerationController::class, 'restore'])->middleware('permission:restore_data::pembanding');
            Route::delete('/pembandings/{id}', [ModerationController::class, 'forceDelete'])->middleware('permission:force_delete_data::pembanding');
        });

        // Export Data
        Route::prefix('exports')->group(function () {
            Route::get('/configuration', [ExportController::class, 'configuration'])->middleware('permission:view_export');
            Route::post('/preview', [ExportController::class, 'preview'])->middleware('permission:export_data::pembanding');
            Route::get('/runs', [ExportController::class, 'runs'])->middleware('permission:view_export');
            Route::post('/runs', [ExportController::class, 'storeRun'])->middleware('permission:export_data::pembanding');
            Route::get('/runs/{exportRun}', [ExportController::class, 'runStatus'])->middleware('permission:export_data::pembanding');
            Route::get('/runs/{exportRun}/download', [ExportController::class, 'downloadRun'])->middleware('permission:export_data::pembanding');
            Route::post('/runs/{exportRun}/retry', [ExportController::class, 'retryRun'])->middleware('permission:export_data::pembanding');
            Route::get('/download', [ExportController::class, 'download'])->middleware('permission:export_data::pembanding');
        });

        // Activity Logs
        Route::prefix('activity-logs')->group(function () {
            Route::get('/', [ActivityLogController::class, 'index'])->middleware('permission:view_activity_log');
            Route::get('/{id}', [ActivityLogController::class, 'show'])->middleware('permission:view_activity_log');
        });

        // System Settings
        Route::prefix('settings')->group(function () {
            Route::get('/', [SettingController::class, 'index'])->middleware('permission:view_settings');
            Route::put('/', [SettingController::class, 'update'])->middleware('permission:update_settings');
            Route::post('/', [SettingController::class, 'update'])->middleware('permission:update_settings'); // multipart upload
            Route::post('/clear-cache', [SettingController::class, 'clearCache'])->middleware('permission:clear_cache');
        });

        Route::prefix('integrations')->middleware('permission:manage_integrations')->group(function () {
            Route::get('/', [IntegrationController::class, 'index']);
            Route::get('/scopes', [IntegrationController::class, 'scopes']);
            Route::get('/{integration}', [IntegrationController::class, 'show']);
            Route::middleware('throttle:api-write')->group(function () {
                Route::post('/', [IntegrationController::class, 'store']);
                Route::patch('/{integration}', [IntegrationController::class, 'update']);
                Route::post('/{integration}/keys', [IntegrationController::class, 'issueKey']);
                Route::delete('/{integration}/keys/{key}', [IntegrationController::class, 'revokeKey']);
            });
        });

        // Backup & Restore
        Route::prefix('backup')->group(function () {
            Route::get('/artifacts', [BackupController::class, 'index'])->middleware('permission:view_backup');
            Route::post('/artifacts', [BackupArtifactController::class, 'store'])->middleware(['permission:create_database_backup|create_uploads_backup', 'throttle:5,1']);
            Route::post('/imports', [BackupArtifactController::class, 'import'])->middleware(['permission:import_backup', 'throttle:5,1']);
            Route::get('/artifacts/{artifact}/download', [BackupFileController::class, 'download'])->middleware('permission:download_backup');
            Route::post('/artifacts/{artifact}/verify', [BackupArtifactController::class, 'verify'])->middleware(['permission:verify_backup', 'throttle:10,1']);
            Route::delete('/artifacts/{artifact}', [BackupFileController::class, 'destroy'])->middleware('permission:delete_backup');
            Route::post('/artifacts/{artifact}/restore-uploads', [BackupRestoreController::class, 'uploads'])
                ->middleware(['role:super_admin', 'permission:restore_uploads_backup', 'throttle:2,10']);
            Route::post('/artifacts/{artifact}/restore-database', [BackupRestoreController::class, 'database'])
                ->middleware(['role:super_admin', 'permission:restore_database_backup']);
        });

    });

    // Explicit shared read/search routes. Keep these after specific pembanding routes.
    Route::get('/pembandings', [DataPembandingController::class, 'index'])
        ->middleware('consumer:pembandings:read,view_any_data::pembanding');
    Route::get('/pembandings/{id}', [DataPembandingController::class, 'show'])
        ->middleware('consumer:pembandings:read,view_any_data::pembanding');
    Route::get('/pembandings/{id}/similar', [DataPembandingController::class, 'similarById'])
        ->middleware('consumer:pembandings:similar,view_any_data::pembanding');
    Route::prefix('locations')->middleware('consumer:locations:read')->group(function () {
        Route::get('/provinces', [LocationController::class, 'provinces']);
        Route::get('/regencies', [LocationController::class, 'regencies']);
        Route::get('/districts', [LocationController::class, 'districts']);
        Route::get('/villages', [LocationController::class, 'villages']);
    });
    Route::get('/dictionaries/{type}', [DictionaryController::class, 'index'])
        ->middleware('consumer:dictionaries:read');
});
