<?php

namespace App\Services\Backup;

use App\Enums\BackupType;

final class BackupPayloadFactory
{
    public function __construct(
        private readonly DatabaseBackupService $database,
        private readonly UploadsBackupService $uploads,
        private readonly BackupManifestFactory $manifestFactory,
    ) {}

    public function create(BackupType $type, string $temporary): array
    {
        $payloads = [];
        if ($type->includesDatabase()) {
            $path = "{$temporary}/database.sql";
            $this->database->create($path);
            $payloads[] = $this->manifestFactory->payload('payload/database.sql', $path);
        }
        if ($type->includesUploads()) {
            $path = "{$temporary}/uploads.zip";
            $this->uploads->create($path);
            $payloads[] = $this->manifestFactory->payload('payload/uploads.zip', $path);
        }

        return $payloads;
    }
}
