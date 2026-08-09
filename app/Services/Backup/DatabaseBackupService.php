<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;

final class DatabaseBackupService
{
    public function create(string $outputPath): void
    {
        $connectionName = (string) config('database.default');
        $connection = (array) config("database.connections.{$connectionName}", []);
        $driver = $connection['driver'] ?? null;

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('Backup database hanya mendukung MySQL atau MariaDB.');
        }

        $database = (string) ($connection['database'] ?? '');
        if ($database === '') {
            throw new RuntimeException('Konfigurasi database tidak lengkap.');
        }

        File::ensureDirectoryExists(dirname($outputPath));
        $optionFile = new MySqlOptionFile;
        $configPath = $optionFile->create($connection);

        try {
            $process = new Process([
                (string) config('system_backup.mysqldump_binary', 'mysqldump'),
                "--defaults-extra-file={$configPath}",
                '--single-transaction',
                '--quick',
                '--skip-lock-tables',
                '--routines',
                '--events',
                '--triggers',
                '--hex-blob',
                '--no-tablespaces',
                "--result-file={$outputPath}",
                $database,
            ], base_path(), null, null, (float) config('system_backup.process_timeout', 3600));
            $process->run();

            if (! $process->isSuccessful() || ! File::exists($outputPath) || File::size($outputPath) === 0) {
                File::delete($outputPath);
                throw new RuntimeException('mysqldump gagal membuat backup database lengkap.');
            }
        } finally {
            $optionFile->delete();
        }
    }
}
