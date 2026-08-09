<?php

namespace App\Services\Backup;

use RuntimeException;
use ZipArchive;

final class SafeZipEntryValidator
{
    public function __construct(private readonly BackupUploadPathPolicy $pathPolicy) {}

    public function validate(
        ZipArchive $zip,
        int $index,
        array $stat,
        array &$seen,
        int $total,
    ): string {
        $name = $this->name((string) ($stat['name'] ?? ''), $seen);
        $size = (int) ($stat['size'] ?? 0);
        $compressed = max(1, (int) ($stat['comp_size'] ?? 0));
        $maximum = (int) config('system_backup.max_extracted_megabytes', 2048) * 1024 * 1024;

        if ($index >= (int) config('system_backup.max_zip_entries', 10000)
            || $total + $size > $maximum
            || ($size / $compressed) > (int) config('system_backup.max_compression_ratio', 200)) {
            throw new RuntimeException('Archive uploads melewati batas keamanan.');
        }

        $zip->getExternalAttributesIndex($index, $operatingSystem, $attributes);
        $mode = ((int) $attributes >> 16) & 0xF000;
        if ($mode !== 0 && ! in_array($mode, [0x4000, 0x8000], true)) {
            throw new RuntimeException('Archive uploads memuat symlink atau special file.');
        }

        return $name;
    }

    private function name(string $name, array &$seen): string
    {
        $normalized = $this->pathPolicy->validate($name, str_ends_with($name, '/'));
        $key = strtolower(rtrim($normalized, '/'));
        if (isset($seen[$key])) {
            throw new RuntimeException('Archive uploads memuat path duplikat.');
        }
        $seen[$key] = true;

        return $normalized;
    }
}
