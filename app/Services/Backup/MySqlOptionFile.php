<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\File;

final class MySqlOptionFile
{
    private ?string $path = null;

    public function create(array $connection): string
    {
        $directory = config('system_backup.root').DIRECTORY_SEPARATOR.'tmp';
        File::ensureDirectoryExists($directory);
        $this->path = $directory.DIRECTORY_SEPARATOR.'mysql-'.bin2hex(random_bytes(12)).'.cnf';
        $content = implode(PHP_EOL, [
            '[client]',
            'host='.$this->quote($connection['host'] ?? '127.0.0.1'),
            'port='.(int) ($connection['port'] ?? 3306),
            'user='.$this->quote($connection['username'] ?? ''),
            'password='.$this->quote($connection['password'] ?? ''),
            'default-character-set='.$this->quote($connection['charset'] ?? 'utf8mb4'),
        ]).PHP_EOL;

        File::put($this->path, $content, true);
        @chmod($this->path, 0600);

        return $this->path;
    }

    public function delete(): void
    {
        if ($this->path !== null) {
            File::delete($this->path);
            $this->path = null;
        }
    }

    private function quote(mixed $value): string
    {
        $value = str_replace(["\r", "\n"], '', (string) $value);

        return '"'.addcslashes($value, '\\"').'"';
    }
}
