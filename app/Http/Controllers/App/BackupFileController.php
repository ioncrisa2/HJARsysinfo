<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\App\Concerns\AuthorizesPermissions;
use App\Http\Controllers\Controller;
use App\Services\Backup\BackupCatalogService;
use App\Services\Backup\LegacyBackupCatalogService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupFileController extends Controller
{
    use AuthorizesPermissions;

    public function download(
        string $artifact,
        BackupCatalogService $catalog,
        LegacyBackupCatalogService $legacyCatalog,
    ): BinaryFileResponse {
        $this->authorizePermission('download_backup');
        try {
            $item = str_starts_with($artifact, 'legacy-') ? null : $catalog->find($artifact);
        } catch (RuntimeException) {
            abort(404);
        }
        $path = $item?->path ?? $legacyCatalog->path($artifact);
        abort_unless($path && is_file($path), 404);
        $response = response()->download($path, $item?->filename ?? basename($path), [
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setPrivate();
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    public function destroy(string $artifact, BackupCatalogService $catalog): RedirectResponse
    {
        $this->authorizePermission('delete_backup');
        try {
            $item = $catalog->find($artifact);
        } catch (RuntimeException) {
            abort(404);
        }
        $catalog->delete($item);
        activity('backup')->causedBy(request()->user())->event('deleted')
            ->withProperties(['artifact_id' => $item->id, 'type' => $item->type->value])
            ->log('Backup sistem dihapus');

        return back()->with('success', 'Backup berhasil dihapus.');
    }
}
