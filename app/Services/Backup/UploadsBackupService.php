<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

final class UploadsBackupService
{
    public function __construct(private readonly BackupUploadPathPolicy $pathPolicy) {}

    public function create(string $outputPath): void
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Ekstensi ZipArchive belum aktif.');
        }

        File::ensureDirectoryExists(dirname($outputPath));
        $zip = new ZipArchive;

        if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('File backup uploads tidak dapat dibuat.');
        }

        $closed = false;

        try {
            foreach ((array) config('system_backup.upload_roots', []) as $root) {
                $this->addDirectory($zip, storage_path("app/public/{$root}"), $root);
            }
            if (! $zip->close() || ! File::exists($outputPath)) {
                throw new RuntimeException('Backup uploads gagal diselesaikan.');
            }
            $closed = true;
        } catch (\Throwable $exception) {
            if (! $closed) {
                $zip->close();
            }
            File::delete($outputPath);
            throw $exception;
        }
    }

    private function addDirectory(ZipArchive $zip, string $source, string $prefix): void
    {
        if (! File::isDirectory($source)) {
            return;
        }

        $sourceRoot = rtrim((string) realpath($source), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($files as $file) {
            if (! $file->isFile() || $file->isLink()) {
                continue;
            }

            $realPath = $file->getRealPath();
            if ($realPath === false || ! str_starts_with($realPath, $sourceRoot)) {
                continue;
            }

            $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($realPath, strlen($sourceRoot)));
            if ($relative !== '') {
                $name = $this->pathPolicy->validate(trim($prefix, '/').'/'.$relative);
                if (! $zip->addFile($realPath, $name)) {
                    throw new RuntimeException("File upload gagal ditambahkan ke backup: {$name}");
                }
            }
        }
    }
}
