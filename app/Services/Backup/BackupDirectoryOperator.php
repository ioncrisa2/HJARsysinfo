<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\File;

class BackupDirectoryOperator
{
    public function exists(string $path): bool
    {
        return File::isDirectory($path);
    }

    public function ensure(string $path): void
    {
        File::ensureDirectoryExists($path);
    }

    public function move(string $from, string $to): bool
    {
        return rename($from, $to);
    }

    public function delete(string $path): bool
    {
        return ! $this->exists($path) || File::deleteDirectory($path);
    }
}
