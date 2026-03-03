<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class SystemBackupService
{
    public function createDatabaseBackup(): string
    {
        $defaultConnection = (string) config('database.default');
        $connection = config("database.connections.{$defaultConnection}");

        if (($connection['driver'] ?? null) !== 'mysql') {
            throw new RuntimeException('Backup database hanya didukung untuk koneksi MySQL.');
        }

        $database = (string) ($connection['database'] ?? '');
        if ($database === '') {
            throw new RuntimeException('Nama database tidak ditemukan di konfigurasi.');
        }

        $backupDir = storage_path('app/backups/database');
        File::ensureDirectoryExists($backupDir);

        $fileName = 'database-backup-' . now()->format('Ymd_His') . '.sql';
        $outputPath = $backupDir . DIRECTORY_SEPARATOR . $fileName;

        $mysqldumpBinary = (string) env('MYSQLDUMP_BINARY', 'mysqldump');

        $command = [
            $mysqldumpBinary,
            '--host=' . (string) ($connection['host'] ?? '127.0.0.1'),
            '--port=' . (string) ($connection['port'] ?? '3306'),
            '--user=' . (string) ($connection['username'] ?? ''),
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            $database,
            '--result-file=' . $outputPath,
        ];

        $password = (string) ($connection['password'] ?? '');
        if ($password !== '') {
            $command[] = '--password=' . $password;
        }

        $process = new Process($command, base_path(), null, null, 3600);
        $process->run();

        if (! $process->isSuccessful() || ! File::exists($outputPath)) {
            throw new RuntimeException(
                'Gagal membuat backup database. Pastikan mysqldump tersedia di server.'
            );
        }

        return $outputPath;
    }

    public function createUploadedFilesBackup(): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Ekstensi PHP ZipArchive belum aktif.');
        }

        $sourcePath = storage_path('app/public');
        if (! is_dir($sourcePath)) {
            throw new RuntimeException('Folder upload tidak ditemukan di storage/app/public.');
        }

        $backupDir = storage_path('app/backups/uploads');
        File::ensureDirectoryExists($backupDir);

        $fileName = 'uploads-backup-' . now()->format('Ymd_His') . '.zip';
        $outputPath = $backupDir . DIRECTORY_SEPARATOR . $fileName;

        $zip = new ZipArchive();
        $result = $zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($result !== true) {
            throw new RuntimeException('Tidak dapat membuat file zip backup upload.');
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourcePath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $filePath = $file->getRealPath();
            if (! $filePath) {
                continue;
            }

            $relativePath = ltrim(str_replace($sourcePath, '', $filePath), DIRECTORY_SEPARATOR);
            $zip->addFile($filePath, $relativePath);
        }

        $zip->close();

        if (! File::exists($outputPath)) {
            throw new RuntimeException('File backup upload gagal dibuat.');
        }

        return $outputPath;
    }
}
