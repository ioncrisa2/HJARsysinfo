<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\File;

final class LegacyBackupCatalogService
{
    public function all(): array
    {
        return collect([
            ['type' => 'database', 'directory' => 'database', 'extension' => 'sql'],
            ['type' => 'uploads', 'directory' => 'uploads', 'extension' => 'zip'],
        ])->flatMap(function (array $source): array {
            $directory = config('system_backup.root').DIRECTORY_SEPARATOR.$source['directory'];
            if (! File::isDirectory($directory)) {
                return [];
            }

            return collect(File::files($directory))
                ->filter(fn (\SplFileInfo $file) => strtolower($file->getExtension()) === $source['extension'])
                ->map(fn (\SplFileInfo $file): array => [
                    'id' => 'legacy-'.hash('sha256', $source['type'].'|'.$file->getFilename()),
                    'type' => $source['type'],
                    'type_label' => $source['type'] === 'database' ? 'Database' : 'Uploaded files',
                    'filename' => $file->getFilename(),
                    'size' => $file->getSize(),
                    'size_label' => number_format($file->getSize() / 1024 / 1024, 1).' MB',
                    'created_at' => date(DATE_ATOM, $file->getMTime()),
                    'restorable' => false,
                ])->all();
        })->sortByDesc('created_at')->values()->all();
    }

    public function path(string $id): ?string
    {
        foreach ($this->all() as $item) {
            if (hash_equals($item['id'], $id)) {
                $directory = $item['type'] === 'database' ? 'database' : 'uploads';

                return config('system_backup.root').DIRECTORY_SEPARATOR.$directory.DIRECTORY_SEPARATOR.$item['filename'];
            }
        }

        return null;
    }
}
