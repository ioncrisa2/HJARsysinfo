<?php

namespace App\Http\Controllers\Api;

use App\Enums\BackupType;
use App\Http\Controllers\Api\Concerns\AuthorizesPermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\BackupCreateRequest;
use App\Http\Requests\App\BackupImportRequest;
use App\Services\Backup\BackupCatalogService;
use App\Services\Backup\SystemBackupService;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Throwable;

#[Group('Backup & Restore', 'Pembuatan, impor, dan verifikasi arsip backup.', weight: 17)]
class BackupArtifactController extends Controller
{
    use ApiResponse;
    use AuthorizesPermissions;

    #[Endpoint(
        title: 'Buat arsip backup baru',
        description: 'Membuat paket backup database, uploads, atau full system.'
    )]
    public function store(BackupCreateRequest $request, SystemBackupService $service): JsonResponse
    {
        $type = BackupType::from($request->validated('type'));
        $this->authorizeCreation($type);

        try {
            $artifact = $service->create($type, $request->user());
            activity('backup')->causedBy($request->user())->event('created')
                ->withProperties(['artifact_id' => $artifact->id, 'type' => $type->value])
                ->log('Backup sistem dibuat');

            $data = $artifact->toArray();
            $data['download_url'] = url("/api/v1/backup/artifacts/{$artifact->id}/download");

            return $this->success($data, 'Backup berhasil dibuat dan sudah dapat diunduh.', 201);
        } catch (Throwable $exception) {
            report($exception);
            $reference = Str::upper(Str::random(8));

            return $this->error("Operasi backup gagal: {$exception->getMessage()} (Referensi: {$reference})", 500, null, 'BACKUP_CREATION_FAILED');
        }
    }

    #[Endpoint(
        title: 'Impor paket arsip backup',
        description: 'Mengunggah dan memverifikasi paket arsip backup eksternal.'
    )]
    public function import(BackupImportRequest $request, SystemBackupService $service): JsonResponse
    {
        $this->authorizePermission('import_backup');

        try {
            $artifact = $service->import($request->file('package'), $request->user());
            activity('backup')->causedBy($request->user())->event('imported')
                ->withProperties(['artifact_id' => $artifact->id, 'type' => $artifact->type->value])
                ->log('Paket backup diimport');

            $data = $artifact->toArray();
            $data['download_url'] = url("/api/v1/backup/artifacts/{$artifact->id}/download");

            return $this->success($data, 'Paket terverifikasi dan berhasil ditambahkan.', 201);
        } catch (Throwable $exception) {
            report($exception);
            $reference = Str::upper(Str::random(8));

            return $this->error("Impor paket backup gagal: {$exception->getMessage()} (Referensi: {$reference})", 422, null, 'BACKUP_IMPORT_FAILED');
        }
    }

    #[Endpoint(
        title: 'Verifikasi integritas arsip backup',
        description: 'Mengecek keabsahan checksum SHA-256 dan cryptographic signature arsip backup.'
    )]
    public function verify(string $artifact, BackupCatalogService $catalog, SystemBackupService $service): JsonResponse
    {
        $this->authorizePermission('verify_backup');

        try {
            $item = $catalog->find($artifact);
            $service->verify($item);

            return $this->success([
                'id' => $item->id,
                'checksum' => $item->checksum,
                'verified' => true,
            ], 'Signature dan checksum backup valid.');
        } catch (Throwable $exception) {
            report($exception);
            $reference = Str::upper(Str::random(8));

            return $this->error("Verifikasi integritas backup gagal: {$exception->getMessage()} (Referensi: {$reference})", 422, null, 'VERIFICATION_FAILED');
        }
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
}
