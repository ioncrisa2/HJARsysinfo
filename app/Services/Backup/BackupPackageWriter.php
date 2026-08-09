<?php

namespace App\Services\Backup;

use App\DTOs\Backup\BackupArtifact;
use App\Enums\BackupType;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

final class BackupPackageWriter
{
    public function __construct(
        private readonly BackupPayloadFactory $payloadFactory,
        private readonly BackupManifestFactory $manifestFactory,
        private readonly BackupPackageInspector $inspector,
    ) {}

    public function create(BackupType $type, User $user, string $origin = 'generated'): BackupArtifact
    {
        $id = strtolower((string) Str::ulid());
        $root = (string) config('system_backup.root');
        $temporary = "{$root}/tmp/{$id}";
        $artifactDirectory = "{$root}/artifacts";
        $filename = "system-backup-{$type->value}-".now()->format('Ymd-His')."-{$id}.sbackup";
        $target = "{$artifactDirectory}/{$filename}";
        $partial = $target.'.partial';
        File::ensureDirectoryExists($temporary);
        File::ensureDirectoryExists($artifactDirectory);

        try {
            $payloads = $this->payloadFactory->create($type, $temporary);
            $createdAt = now()->toIso8601String();
            $manifest = $this->manifestFactory->make($id, $type, $payloads, $user, $createdAt);
            $this->writePackage($partial, $temporary, $manifest);
            $this->inspector->inspect($partial);
            if (! File::move($partial, $target)) {
                throw new RuntimeException('Paket backup gagal dipublikasikan.');
            }
            @chmod($target, 0600);

            return new BackupArtifact(
                id: $id,
                type: $type,
                filename: $filename,
                path: $target,
                size: File::size($target),
                checksum: hash_file('sha256', $target),
                createdAt: $createdAt,
                createdBy: ['id' => $user->id, 'name' => $user->name],
                origin: $origin,
                verified: true,
                verifiedAt: $createdAt,
            );
        } finally {
            File::delete($partial);
            File::deleteDirectory($temporary);
        }
    }

    private function writePackage(string $path, string $temporary, array $manifest): void
    {
        $signingKey = (string) config('system_backup.signing_key');
        if ($signingKey === '') {
            throw new RuntimeException('Signing key backup belum dikonfigurasi.');
        }

        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Paket backup tidak dapat dibuat.');
        }
        $closed = false;

        try {
            $json = json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $added = $zip->addFromString('manifest.json', $json)
                && $zip->addFromString('signature.txt', hash_hmac('sha256', $json, $signingKey));
            foreach ($manifest['payloads'] as $payload) {
                $added = $added && $zip->addFile(
                    $temporary.'/'.basename($payload['name']),
                    $payload['name'],
                );
            }
            if (! $added || ! $zip->close()) {
                throw new RuntimeException('Paket backup gagal diselesaikan.');
            }
            $closed = true;
        } finally {
            if (! $closed) {
                $zip->close();
            }
        }
    }
}
