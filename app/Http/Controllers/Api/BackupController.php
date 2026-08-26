<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesPermissions;
use App\Http\Controllers\Controller;
use App\Services\Backup\BackupCatalogService;
use App\Services\Backup\BackupReadinessService;
use App\Services\Backup\LegacyBackupCatalogService;
use App\Support\AppAccess;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Backup & Restore', 'Katalog berkas backup, verifikasi integritas, pembuatan arsip, dan pemulihan sistem.', weight: 17)]
class BackupController extends Controller
{
    use ApiResponse;
    use AuthorizesPermissions;

    #[Endpoint(
        title: 'Lihat katalog berkas backup dan status kesiapan',
        description: 'Mengembalikan daftar arsip backup yang tersedia di server, riwayat backup legacy, status kesiapan storage/mysqldump, dan izin operasi.'
    )]
    public function index(
        Request $request,
        BackupCatalogService $catalog,
        LegacyBackupCatalogService $legacyCatalog,
        BackupReadinessService $readiness,
    ): JsonResponse {
        $this->authorizePermission('view_backup');
        $user = $request->user();

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

        $artifacts = collect($catalog->all())->map(function ($item): array {
            $data = $item->toArray();
            $data['download_url'] = url("/api/v1/backup/artifacts/{$item->id}/download");

            return $data;
        })->all();

        return $this->success([
            'artifacts' => $artifacts,
            'legacy_artifacts' => $legacyCatalog->all(),
            'readiness' => $readiness->status(),
            'can' => $can,
        ], 'Katalog backup berhasil diambil.');
    }
}
