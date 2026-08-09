<?php

namespace App\Services\Backup;

use App\DTOs\Backup\BackupArtifact;
use App\Enums\BackupType;
use App\Exceptions\IncompleteUploadsRollbackException;
use App\Models\User;
use Illuminate\Support\Str;
use Throwable;

final class SystemRestoreService
{
    public function __construct(
        private readonly BackupOperationLock $lock,
        private readonly BackupPackageInspector $inspector,
        private readonly BackupPackageWriter $writer,
        private readonly BackupCatalogService $catalog,
        private readonly UploadsRestoreService $uploads,
        private readonly BackupAuditService $audit,
    ) {}

    public function restoreUploads(BackupArtifact $artifact, User $actor): BackupArtifact
    {
        return $this->lock->run(function () use ($artifact, $actor): BackupArtifact {
            $operationId = strtolower((string) Str::ulid());
            $context = [
                'operation_id' => $operationId,
                'artifact_id' => $artifact->id,
                'actor' => ['id' => $actor->id, 'name' => $actor->name],
            ];
            $this->audit->record('uploads_restore_started', $context);

            try {
                $this->guard($artifact);
                $safety = $this->writer->create(BackupType::Uploads, $actor, 'safety');
                $this->catalog->save($safety);
                $this->uploads->restore($artifact, $operationId);
                $restored = $artifact->withRestore([
                    'operation_id' => $operationId,
                    'status' => 'completed',
                    'restored_at' => now()->toIso8601String(),
                    'restored_by' => ['id' => $actor->id, 'name' => $actor->name],
                    'safety_artifact_id' => $safety->id,
                ]);
                $this->catalog->save($restored);
                $this->audit->record('uploads_restore_completed', [
                    ...$context, 'safety_artifact_id' => $safety->id,
                ]);

                return $restored;
            } catch (Throwable $exception) {
                $this->audit->record('uploads_restore_failed', [
                    ...$context,
                    'failure_class' => $exception::class,
                    'recovery_path' => $exception instanceof IncompleteUploadsRollbackException
                        ? $exception->recoveryPath
                        : null,
                ]);
                throw $exception;
            }
        });
    }

    private function guard(BackupArtifact $artifact): void
    {
        if (! (bool) config('system_backup.restore_enabled')) {
            throw new \RuntimeException('Restore belum diaktifkan oleh operator.');
        }
        if (! $artifact->type->includesUploads()) {
            throw new \RuntimeException('Backup ini tidak memuat uploaded files.');
        }
        $this->inspector->inspect($artifact->path);
        if (! hash_equals($artifact->checksum, hash_file('sha256', $artifact->path))) {
            throw new \RuntimeException('Checksum paket backup tidak cocok.');
        }
    }
}
