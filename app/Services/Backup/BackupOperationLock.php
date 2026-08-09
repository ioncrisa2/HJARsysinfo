<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\File;
use RuntimeException;

final class BackupOperationLock
{
    public function run(callable $operation): mixed
    {
        $directory = config('system_backup.root').DIRECTORY_SEPARATOR.'locks';
        File::ensureDirectoryExists($directory);
        $handle = fopen($directory.DIRECTORY_SEPARATOR.'system-backup.lock', 'c+');

        if ($handle === false || ! flock($handle, LOCK_EX | LOCK_NB)) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            throw new RuntimeException('Operasi backup lain sedang berjalan.');
        }

        try {
            return $operation();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
