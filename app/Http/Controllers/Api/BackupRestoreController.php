<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\IncompleteUploadsRollbackException;
use App\Http\Controllers\Api\Concerns\AuthorizesPermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\BackupRestoreRequest;
use App\Services\Backup\BackupCatalogService;
use App\Services\Backup\SystemRestoreService;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

#[Group('Backup & Restore', 'Proses pemulihan (restore) berkas uploads dan database.', weight: 17)]
class BackupRestoreController extends Controller
{
    use ApiResponse;
    use AuthorizesPermissions;

    #[Endpoint(
        title: 'Pulihkan berkas uploads dari arsip backup',
        description: 'Memulihkan direktori upload foto properti dari paket backup yang diverifikasi (hanya Super Admin).'
    )]
    public function uploads(
        BackupRestoreRequest $request,
        string $artifact,
        BackupCatalogService $catalog,
        SystemRestoreService $restore,
    ): JsonResponse {
        $this->authorizePermission('restore_uploads_backup');
        abort_unless($request->user()->hasRole('super_admin'), 403, 'Hanya super admin yang dapat melakukan pemulihan sistem.');

        try {
            $item = $catalog->find($artifact);
        } catch (RuntimeException) {
            abort(404, 'Berkas backup tidak ditemukan.');
        }

        if ($request->validated('confirmation') !== "RESTORE {$item->id}") {
            return $this->error("Konfirmasi tidak valid. Ketik persis 'RESTORE {$item->id}'.", 422, null, 'INVALID_CONFIRMATION');
        }

        try {
            $restore->restoreUploads($item, $request->user());

            return $this->success([
                'restored_artifact_id' => $item->id,
            ], 'Uploaded files berhasil dipulihkan. Backup keselamatan otomatis telah dibuat.');
        } catch (IncompleteUploadsRollbackException $exception) {
            report($exception);
            $reference = Str::upper(Str::random(8));

            return $this->error("Rollback filesystem tidak lengkap. Jangan ubah file upload dan hubungi operator. Referensi: {$reference}", 500, null, 'RESTORE_ROLLBACK_INCOMPLETE');
        } catch (Throwable $exception) {
            report($exception);
            $reference = Str::upper(Str::random(8));

            return $this->error("Restore gagal dan perubahan dibatalkan. Referensi: {$reference}", 500, null, 'RESTORE_FAILED');
        }
    }

    #[Endpoint(
        title: 'Pulihkan database dari arsip backup (Tertunda/Deferred)',
        description: 'Endpoint reservasi untuk pemulihan database via API (memerlukan CLI/maintenance runner).'
    )]
    public function database(): JsonResponse
    {
        return $this->error('Pemulihan database interaktif saat ini hanya dapat dijalankan melalui CLI/console runner.', 501, null, 'NOT_IMPLEMENTED');
    }
}
