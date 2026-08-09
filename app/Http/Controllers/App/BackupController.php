<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\App\Concerns\AuthorizesPermissions;
use App\Http\Controllers\Controller;
use App\Services\Backup\BackupCatalogService;
use App\Services\Backup\BackupReadinessService;
use App\Services\Backup\LegacyBackupCatalogService;
use App\Support\AppAccess;
use Inertia\Inertia;
use Inertia\Response;

class BackupController extends Controller
{
    use AuthorizesPermissions;

    public function __invoke(
        BackupCatalogService $catalog,
        LegacyBackupCatalogService $legacyCatalog,
        BackupReadinessService $readiness,
    ): Response {
        $this->authorizePermission('view_backup');
        $user = request()->user();
        $can = AppAccess::capabilityMap($user, [
            'create_database' => 'create_database_backup',
            'create_uploads' => 'create_uploads_backup',
            'download' => 'download_backup',
            'import' => 'import_backup',
            'verify' => 'verify_backup',
            'restore_database' => 'restore_database_backup',
            'restore_uploads' => 'restore_uploads_backup',
            'delete' => 'delete_backup',
        ]);
        $can['restore_operator'] = $user?->hasRole('super_admin') ?? false;

        return Inertia::render('Backup/Index', [
            'artifacts' => collect($catalog->all())->map(fn ($item) => $item->toArray())->all(),
            'legacyArtifacts' => $legacyCatalog->all(),
            'readiness' => $readiness->status(),
            'can' => $can,
        ]);
    }
}
