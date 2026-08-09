<?php

namespace App\Services\Backup;

use App\DTOs\Backup\BackupArtifact;
use App\Exceptions\IncompleteUploadsRollbackException;
use RuntimeException;
use Throwable;

final class UploadsRestoreService
{
    public function __construct(
        private readonly BackupPackageInspector $inspector,
        private readonly SafeZipExtractor $extractor,
        private readonly BackupDirectoryOperator $directories,
    ) {}

    public function restore(BackupArtifact $artifact, string $operationId): void
    {
        $manifest = $this->inspector->inspect($artifact->path);
        $payload = collect($manifest['payloads'])->firstWhere('name', 'payload/uploads.zip');
        if (! $payload) {
            throw new RuntimeException('Paket tidak memiliki backup uploaded files.');
        }

        $root = (string) config('system_backup.root');
        $workspace = "{$root}/tmp/restore-{$operationId}";
        $archive = "{$workspace}/uploads.zip";
        $staging = "{$workspace}/staging";
        $rollback = "{$workspace}/rollback";
        $this->directories->ensure($workspace);

        $preserveWorkspace = false;

        try {
            $this->inspector->extractPayload($artifact->path, 'payload/uploads.zip', $archive);
            $this->extractor->extract($archive, $staging);
            $this->swap($staging, $rollback);
        } catch (IncompleteUploadsRollbackException $exception) {
            $preserveWorkspace = true;
            throw $exception;
        } finally {
            if (! $preserveWorkspace) {
                $this->directories->delete($workspace);
            }
        }
    }

    private function swap(string $staging, string $rollback): void
    {
        $swapped = [];

        try {
            foreach ((array) config('system_backup.upload_roots', []) as $root) {
                $live = storage_path("app/public/{$root}");
                $incoming = "{$staging}/{$root}";
                $previous = "{$rollback}/{$root}";
                $this->directories->ensure($incoming);
                $this->directories->ensure(dirname($previous));

                $hadLive = $this->directories->exists($live);
                if ($hadLive && ! $this->directories->move($live, $previous)) {
                    throw new RuntimeException('Folder upload aktif tidak dapat dipindahkan.');
                }
                if (! $this->directories->move($incoming, $live)) {
                    if ($hadLive && ! $this->directories->move($previous, $live)) {
                        throw new IncompleteUploadsRollbackException($rollback);
                    }
                    throw new RuntimeException('Folder upload hasil restore tidak dapat diaktifkan.');
                }
                $swapped[] = compact('live', 'previous');
            }
        } catch (Throwable $exception) {
            $this->rollback($swapped, $rollback, $exception);
        }
    }

    private function rollback(array $swapped, string $rollback, Throwable $cause): never
    {
        $failed = false;

        foreach (array_reverse($swapped) as $item) {
            if (! $this->directories->delete($item['live'])) {
                $failed = true;

                continue;
            }
            if ($this->directories->exists($item['previous'])
                && ! $this->directories->move($item['previous'], $item['live'])) {
                $failed = true;
            }
        }

        if ($failed || $cause instanceof IncompleteUploadsRollbackException) {
            throw new IncompleteUploadsRollbackException($rollback, $cause);
        }

        throw $cause;
    }
}
