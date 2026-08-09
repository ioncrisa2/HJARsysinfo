<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\File;

final class BackupReadinessService
{
    public function status(): array
    {
        $root = (string) config('system_backup.root');
        File::ensureDirectoryExists($root);
        $restoreEnabled = (bool) config('system_backup.restore_enabled');

        return [
            'zip' => class_exists(\ZipArchive::class),
            'storage_writable' => File::isWritable($root),
            'signing_key' => filled(config('system_backup.signing_key')),
            'restore_enabled' => $restoreEnabled,
            'uploads_restore_ready' => $restoreEnabled
                && class_exists(\ZipArchive::class)
                && File::isWritable($root)
                && filled(config('system_backup.signing_key')),
            'database_restore_ready' => false,
            'database_restore_note' => 'Restore database belum diimplementasikan dan tetap dikunci.',
            'max_package_mb' => (int) config('system_backup.max_package_megabytes', 1024),
            'retention_days' => (int) config('system_backup.retention_days', 30),
        ];
    }
}
