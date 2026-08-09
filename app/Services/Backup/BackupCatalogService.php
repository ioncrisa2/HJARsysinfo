<?php

namespace App\Services\Backup;

use App\DTOs\Backup\BackupArtifact;
use Illuminate\Support\Facades\File;
use RuntimeException;

final class BackupCatalogService
{
    /** @return array<int, BackupArtifact> */
    public function all(): array
    {
        $directory = $this->manifestDirectory();
        if (! File::isDirectory($directory)) {
            return [];
        }

        return collect(File::files($directory))
            ->map(fn (\SplFileInfo $file) => $this->read($file->getPathname()))
            ->filter(fn (?BackupArtifact $item) => $item && $this->isAllowedArtifactPath($item->path))
            ->sortByDesc(fn (BackupArtifact $item) => $item->createdAt)
            ->values()
            ->all();
    }

    public function find(string $id): BackupArtifact
    {
        $path = $this->manifestDirectory().DIRECTORY_SEPARATOR.$this->safeId($id).'.json';
        $artifact = $this->read($path);

        if (! $artifact || ! $this->isAllowedArtifactPath($artifact->path)) {
            throw new RuntimeException('Backup tidak ditemukan.');
        }

        return $artifact;
    }

    public function save(BackupArtifact $artifact): void
    {
        File::ensureDirectoryExists($this->manifestDirectory());
        $target = $this->manifestDirectory().DIRECTORY_SEPARATOR.$artifact->id.'.json';
        File::replace(
            $target,
            json_encode($artifact->toStorageArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
            0600,
        );
    }

    public function delete(BackupArtifact $artifact): void
    {
        File::delete($artifact->path);
        File::delete($this->manifestDirectory().DIRECTORY_SEPARATOR.$artifact->id.'.json');
    }

    private function read(string $path): ?BackupArtifact
    {
        if (! File::exists($path)) {
            return null;
        }

        try {
            return BackupArtifact::fromArray(json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR));
        } catch (\Throwable) {
            return null;
        }
    }

    private function manifestDirectory(): string
    {
        return config('system_backup.root').DIRECTORY_SEPARATOR.'manifests';
    }

    private function isAllowedArtifactPath(string $path): bool
    {
        $realPath = realpath($path);
        $root = realpath(config('system_backup.root').DIRECTORY_SEPARATOR.'artifacts');

        return $realPath !== false
            && $root !== false
            && str_starts_with($realPath, rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)
            && is_file($realPath);
    }

    private function safeId(string $id): string
    {
        if (! preg_match('/^[0-9a-zA-Z-]{10,80}$/', $id)) {
            throw new RuntimeException('ID backup tidak valid.');
        }

        return $id;
    }
}
