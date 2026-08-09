<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\File;

final class BackupAuditService
{
    public function record(string $event, array $context): void
    {
        $directory = config('system_backup.root').DIRECTORY_SEPARATOR.'audit';
        File::ensureDirectoryExists($directory);
        $record = [
            'timestamp' => now()->toIso8601String(),
            'event' => $event,
            ...$context,
        ];
        file_put_contents(
            $directory.DIRECTORY_SEPARATOR.'operations.jsonl',
            json_encode($record, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL,
            FILE_APPEND | LOCK_EX,
        );
    }
}
