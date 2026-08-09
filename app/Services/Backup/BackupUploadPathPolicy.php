<?php

namespace App\Services\Backup;

use RuntimeException;

final class BackupUploadPathPolicy
{
    public function validate(string $name, bool $directory = false): string
    {
        if ($name === '' || str_contains($name, "\0") || str_contains($name, '\\')
            || str_starts_with($name, '/') || preg_match('/^[a-zA-Z]:/', $name)) {
            throw new RuntimeException('Archive uploads memuat path absolut atau tidak valid.');
        }

        $segments = array_values(array_filter(
            explode('/', rtrim($name, '/')),
            fn (string $part) => $part !== '',
        ));
        if ($segments === [] || in_array('..', $segments, true)
            || collect($segments)->contains(fn (string $part) => str_starts_with($part, '.'))
            || ! in_array($segments[0], (array) config('system_backup.upload_roots', []), true)) {
            throw new RuntimeException('Archive uploads memuat path yang tidak diizinkan.');
        }

        if (! $directory) {
            $extension = strtolower(pathinfo(end($segments), PATHINFO_EXTENSION));
            if (! in_array($extension, (array) config('system_backup.allowed_upload_extensions', []), true)) {
                throw new RuntimeException('Archive uploads memuat tipe file yang tidak diizinkan.');
            }
        }

        return implode('/', $segments).($directory ? '/' : '');
    }
}
