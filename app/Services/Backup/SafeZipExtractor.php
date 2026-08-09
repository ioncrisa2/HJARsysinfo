<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

final class SafeZipExtractor
{
    public function __construct(private readonly SafeZipEntryValidator $validator) {}

    public function extract(string $archive, string $target): void
    {
        $zip = new ZipArchive;
        if ($zip->open($archive, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException('Archive uploads tidak dapat dibuka.');
        }

        File::ensureDirectoryExists($target);
        $seen = [];
        $total = 0;

        try {
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $stat = $zip->statIndex($index);
                $name = $this->validator->validate($zip, $index, $stat, $seen, $total);
                $total += (int) ($stat['size'] ?? 0);
                $this->writeEntry($zip, $index, $name, $target, (int) ($stat['size'] ?? 0));
            }
        } finally {
            $zip->close();
        }
    }

    private function writeEntry(
        ZipArchive $zip,
        int $index,
        string $name,
        string $target,
        int $expectedSize,
    ): void {
        $path = $target.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, rtrim($name, '/'));
        if (str_ends_with($name, '/')) {
            File::ensureDirectoryExists($path);

            return;
        }

        File::ensureDirectoryExists(dirname($path));
        $input = $zip->getStream($zip->getNameIndex($index));
        if ($input === false) {
            throw new RuntimeException('File archive uploads gagal dibaca.');
        }
        $output = fopen($path, 'xb');
        if ($output === false) {
            fclose($input);
            throw new RuntimeException('File archive uploads gagal ditulis ke staging.');
        }
        try {
            if (stream_copy_to_stream($input, $output) !== $expectedSize) {
                throw new RuntimeException('File archive uploads tidak berhasil ditulis lengkap.');
            }
        } finally {
            fclose($input);
            fclose($output);
        }
        @chmod($path, 0640);
    }
}
