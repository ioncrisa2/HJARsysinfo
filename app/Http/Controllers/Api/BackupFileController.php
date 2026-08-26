<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesPermissions;
use App\Http\Controllers\Controller;
use App\Services\Backup\BackupCatalogService;
use App\Services\Backup\LegacyBackupCatalogService;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Group('Backup & Restore', 'Pengunduhan dan penghapusan berkas backup.', weight: 17)]
class BackupFileController extends Controller
{
    use ApiResponse;
    use AuthorizesPermissions;

    #[Endpoint(
        title: 'Unduh berkas arsip backup',
        description: 'Mengunduh file fisik arsip backup (.tar.gz / zip).'
    )]
    public function download(
        string $artifact,
        BackupCatalogService $catalog,
        LegacyBackupCatalogService $legacyCatalog,
    ): BinaryFileResponse {
        $this->authorizePermission('download_backup');

        try {
            $item = str_starts_with($artifact, 'legacy-') ? null : $catalog->find($artifact);
        } catch (RuntimeException) {
            abort(404, 'Berkas backup tidak ditemukan.');
        }

        $path = $item?->path ?? $legacyCatalog->path($artifact);
        abort_unless($path && is_file($path), 404, 'File backup tidak ditemukan pada storage.');

        $response = response()->download($path, $item?->filename ?? basename($path), [
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setPrivate();
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    #[Endpoint(
        title: 'Hapus arsip backup',
        description: 'Menghapus berkas arsip backup dari server storage.'
    )]
    public function destroy(string $artifact, BackupCatalogService $catalog): JsonResponse
    {
        $this->authorizePermission('delete_backup');

        try {
            $item = $catalog->find($artifact);
        } catch (RuntimeException) {
            abort(404, 'Berkas backup tidak ditemukan.');
        }

        $catalog->delete($item);

        activity('backup')->causedBy(request()->user())->event('deleted')
            ->withProperties(['artifact_id' => $item->id, 'type' => $item->type->value])
            ->log('Backup sistem dihapus');

        return $this->success(null, 'Backup berhasil dihapus.');
    }
}
