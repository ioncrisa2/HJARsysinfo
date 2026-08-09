<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

final class BackupPayloadStreamService
{
    public function checksum(ZipArchive $zip, string $name): array
    {
        $stream = $zip->getStream($name);
        if ($stream === false) {
            throw new RuntimeException('Payload paket backup tidak lengkap.');
        }

        try {
            $hash = hash_init('sha256');
            $bytes = hash_update_stream($hash, $stream);

            return ['bytes' => $bytes, 'sha256' => hash_final($hash)];
        } finally {
            fclose($stream);
        }
    }

    public function extract(ZipArchive $zip, string $name, string $target, int $size): void
    {
        $input = $zip->getStream($name);
        if ($input === false) {
            throw new RuntimeException('Payload backup tidak dapat dibaca.');
        }
        $output = fopen($target, 'wb');
        if ($output === false) {
            fclose($input);
            throw new RuntimeException('Payload backup tidak dapat diekstrak.');
        }

        try {
            try {
                if (stream_copy_to_stream($input, $output) !== $size) {
                    throw new RuntimeException('Payload backup tidak berhasil diekstrak lengkap.');
                }
            } finally {
                fclose($input);
                fclose($output);
            }
        } catch (\Throwable $exception) {
            File::delete($target);
            throw $exception;
        }
    }
}
