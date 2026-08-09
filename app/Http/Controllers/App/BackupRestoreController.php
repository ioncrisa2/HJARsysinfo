<?php

namespace App\Http\Controllers\App;

use App\Exceptions\IncompleteUploadsRollbackException;
use App\Http\Controllers\App\Concerns\AuthorizesPermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\BackupRestoreRequest;
use App\Services\Backup\BackupCatalogService;
use App\Services\Backup\SystemRestoreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class BackupRestoreController extends Controller
{
    use AuthorizesPermissions;

    public function uploads(
        BackupRestoreRequest $request,
        string $artifact,
        BackupCatalogService $catalog,
        SystemRestoreService $restore,
    ): RedirectResponse {
        $this->authorizePermission('restore_uploads_backup');
        abort_unless($request->user()->hasRole('super_admin'), 403);
        try {
            $item = $catalog->find($artifact);
        } catch (RuntimeException) {
            abort(404);
        }

        if ($request->validated('confirmation') !== "RESTORE {$item->id}") {
            throw ValidationException::withMessages([
                'confirmation' => "Ketik persis RESTORE {$item->id}.",
            ]);
        }

        try {
            $restore->restoreUploads($item, $request->user());

            return back()->with('success', 'Uploaded files berhasil dipulihkan. Backup keselamatan otomatis telah dibuat.');
        } catch (IncompleteUploadsRollbackException $exception) {
            report($exception);
            $reference = Str::upper(Str::random(8));

            return back()->with(
                'error',
                "Rollback filesystem tidak lengkap. Jangan ubah file upload dan hubungi operator. Referensi: {$reference}",
            );
        } catch (Throwable $exception) {
            report($exception);
            $reference = Str::upper(Str::random(8));

            return back()->with('error', "Restore gagal dan perubahan dibatalkan. Referensi: {$reference}");
        }
    }
}
