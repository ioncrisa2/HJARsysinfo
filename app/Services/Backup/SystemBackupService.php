<?php

namespace App\Services\Backup;

use App\DTOs\Backup\BackupArtifact;
use App\Enums\BackupType;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

final class SystemBackupService
{
    public function __construct(
        private readonly BackupOperationLock $lock,
        private readonly BackupPackageWriter $writer,
        private readonly BackupPackageInspector $inspector,
        private readonly BackupCatalogService $catalog,
    ) {}

    public function create(BackupType $type, User $user): BackupArtifact
    {
        return $this->lock->run(function () use ($type, $user): BackupArtifact {
            $artifact = $this->writer->create($type, $user);
            $this->catalog->save($artifact);

            return $artifact;
        });
    }

    public function import(UploadedFile $file, User $user): BackupArtifact
    {
        return $this->lock->run(function () use ($file): BackupArtifact {
            $root = (string) config('system_backup.root');
            $temporary = "{$root}/tmp/import-".Str::uuid().'.partial';
            File::ensureDirectoryExists(dirname($temporary));
            if (! File::copy($file->getRealPath(), $temporary)) {
                throw new RuntimeException('Paket backup gagal disalin ke staging.');
            }

            try {
                $manifest = $this->inspector->inspect($temporary);
                $id = (string) $manifest['id'];
                try {
                    $this->catalog->find($id);
                    throw new RuntimeException('Paket backup ini sudah pernah diimport.');
                } catch (RuntimeException $exception) {
                    if ($exception->getMessage() !== 'Backup tidak ditemukan.') {
                        throw $exception;
                    }
                }

                $filename = "imported-{$id}.sbackup";
                $target = "{$root}/artifacts/{$filename}";
                File::ensureDirectoryExists(dirname($target));
                if (! File::move($temporary, $target)) {
                    throw new RuntimeException('Paket backup gagal dipublikasikan.');
                }
                @chmod($target, 0600);
                $artifact = new BackupArtifact(
                    id: $id,
                    type: BackupType::from($manifest['type']),
                    filename: $filename,
                    path: $target,
                    size: File::size($target),
                    checksum: hash_file('sha256', $target),
                    createdAt: (string) $manifest['created_at'],
                    createdBy: (array) $manifest['created_by'],
                    origin: 'imported',
                    verified: true,
                    verifiedAt: now()->toIso8601String(),
                );
                $this->catalog->save($artifact);

                return $artifact;
            } finally {
                File::delete($temporary);
            }
        });
    }

    public function verify(BackupArtifact $artifact): BackupArtifact
    {
        $this->inspector->inspect($artifact->path);
        if (! hash_equals($artifact->checksum, hash_file('sha256', $artifact->path))) {
            throw new RuntimeException('Checksum file backup telah berubah.');
        }

        $verified = $artifact->verifiedAt(now()->toIso8601String());
        $this->catalog->save($verified);

        return $verified;
    }
}
