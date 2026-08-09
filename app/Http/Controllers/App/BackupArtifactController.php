<?php

namespace App\Http\Controllers\App;

use App\Enums\BackupType;
use App\Http\Controllers\App\Concerns\AuthorizesPermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\BackupCreateRequest;
use App\Http\Requests\App\BackupImportRequest;
use App\Services\Backup\BackupCatalogService;
use App\Services\Backup\SystemBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Throwable;

class BackupArtifactController extends Controller
{
    use AuthorizesPermissions;

    public function store(BackupCreateRequest $request, SystemBackupService $service): RedirectResponse
    {
        $type = BackupType::from($request->validated('type'));
        $this->authorizeCreation($type);

        return $this->attempt(function () use ($service, $type, $request): string {
            $artifact = $service->create($type, $request->user());
            activity('backup')->causedBy($request->user())->event('created')
                ->withProperties(['artifact_id' => $artifact->id, 'type' => $type->value])
                ->log('Backup sistem dibuat');

            return 'Backup berhasil dibuat dan sudah dapat diunduh.';
        });
    }

    public function import(BackupImportRequest $request, SystemBackupService $service): RedirectResponse
    {
        $this->authorizePermission('import_backup');

        return $this->attempt(function () use ($service, $request): string {
            $artifact = $service->import($request->file('package'), $request->user());
            activity('backup')->causedBy($request->user())->event('imported')
                ->withProperties(['artifact_id' => $artifact->id, 'type' => $artifact->type->value])
                ->log('Paket backup diimport');

            return 'Paket terverifikasi dan berhasil ditambahkan.';
        });
    }

    public function verify(string $artifact, BackupCatalogService $catalog, SystemBackupService $service): RedirectResponse
    {
        $this->authorizePermission('verify_backup');

        return $this->attempt(function () use ($artifact, $catalog, $service): string {
            $service->verify($catalog->find($artifact));

            return 'Signature dan checksum backup valid.';
        });
    }

    private function authorizeCreation(BackupType $type): void
    {
        if ($type->includesDatabase()) {
            $this->authorizePermission('create_database_backup');
        }
        if ($type->includesUploads()) {
            $this->authorizePermission('create_uploads_backup');
        }
    }

    private function attempt(callable $operation): RedirectResponse
    {
        try {
            return back()->with('success', $operation());
        } catch (Throwable $exception) {
            report($exception);
            $reference = Str::upper(Str::random(8));

            return back()->with('error', "Operasi backup gagal. Referensi: {$reference}");
        }
    }
}
