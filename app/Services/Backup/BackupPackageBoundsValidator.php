<?php

namespace App\Services\Backup;

use RuntimeException;
use ZipArchive;

final class BackupPackageBoundsValidator
{
    public function validateMetadata(ZipArchive $zip): void
    {
        if ($zip->numFiles > 4) {
            throw new RuntimeException('Paket backup memuat terlalu banyak entry.');
        }

        foreach (['manifest.json' => 65536, 'signature.txt' => 256] as $name => $maximum) {
            $stat = $zip->statName($name);
            if (is_array($stat) && (int) $stat['size'] > $maximum) {
                throw new RuntimeException('Metadata paket backup melewati batas keamanan.');
            }
        }
    }

    public function validatePayload(array $stat): void
    {
        $size = (int) ($stat['size'] ?? -1);
        $compressed = max(1, (int) ($stat['comp_size'] ?? 0));
        $maximum = (int) config('system_backup.max_package_megabytes', 1024) * 1024 * 1024;

        if ($size < 0 || $size > $maximum
            || ($size / $compressed) > (int) config('system_backup.max_compression_ratio', 200)) {
            throw new RuntimeException('Payload paket backup melewati batas keamanan.');
        }
    }
}
