<?php

namespace App\Services\Backup;

use App\Enums\BackupType;
use RuntimeException;
use ZipArchive;

final class BackupManifestValidator
{
    public function validate(array $manifest, ZipArchive $zip): void
    {
        $type = BackupType::tryFrom((string) ($manifest['type'] ?? ''));
        if (($manifest['schema_version'] ?? null) !== 1
            || ! $type
            || ! preg_match('/^[0-9a-z]{20,40}$/', (string) ($manifest['id'] ?? ''))
            || ($manifest['application'] ?? null) !== config('app.name')
            || ! is_array($manifest['payloads'] ?? null)
            || ($manifest['payloads'] ?? []) === []) {
            throw new RuntimeException('Format manifest paket backup tidak didukung.');
        }

        $expectedPayloads = match ($type) {
            BackupType::Database => ['payload/database.sql'],
            BackupType::Uploads => ['payload/uploads.zip'],
            BackupType::Full => ['payload/database.sql', 'payload/uploads.zip'],
        };
        $payloads = collect($manifest['payloads'])->pluck('name')->sort()->values()->all();
        sort($expectedPayloads);
        if ($payloads !== $expectedPayloads) {
            throw new RuntimeException('Isi paket tidak sesuai dengan tipe backup.');
        }

        $this->validateEntries($zip, $payloads);
    }

    private function validateEntries(ZipArchive $zip, array $payloads): void
    {
        $allowed = collect($payloads)
            ->prepend('signature.txt')
            ->prepend('manifest.json')
            ->sort()
            ->values()
            ->all();
        $actual = [];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            if (! is_string($name) || str_contains($name, "\0")) {
                throw new RuntimeException('Entry paket backup tidak valid.');
            }
            $actual[] = $name;
        }

        sort($actual);
        if ($actual !== $allowed) {
            throw new RuntimeException('Paket backup memuat entry yang tidak diizinkan.');
        }
    }
}
