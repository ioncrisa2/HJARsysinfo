<?php

namespace App\Services\Backup;

use RuntimeException;
use ZipArchive;

final class BackupPackageInspector
{
    public function __construct(
        private readonly BackupManifestValidator $validator,
        private readonly BackupPackageBoundsValidator $bounds,
        private readonly BackupPayloadStreamService $streams,
    ) {}

    public function inspect(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException('Paket backup tidak dapat dibuka.');
        }

        try {
            $this->bounds->validateMetadata($zip);
            $manifestJson = $zip->getFromName('manifest.json');
            $signature = trim((string) $zip->getFromName('signature.txt'));
            if (! is_string($manifestJson) || $signature === '') {
                throw new RuntimeException('Manifest atau signature paket tidak ditemukan.');
            }

            $expected = hash_hmac('sha256', $manifestJson, $this->signingKey());
            if (! hash_equals($expected, $signature)) {
                throw new RuntimeException('Signature paket backup tidak valid.');
            }

            $manifest = json_decode($manifestJson, true, flags: JSON_THROW_ON_ERROR);
            $this->validator->validate($manifest, $zip);

            foreach ($manifest['payloads'] as $payload) {
                $this->verifyPayload($zip, $payload);
            }

            return $manifest;
        } finally {
            $zip->close();
        }
    }

    public function extractPayload(string $packagePath, string $payloadName, string $target): void
    {
        $zip = new ZipArchive;
        if ($zip->open($packagePath, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException('Paket backup tidak dapat dibuka.');
        }

        try {
            $stat = $zip->statName($payloadName);
            if (! is_array($stat)) {
                throw new RuntimeException('Payload backup tidak ditemukan.');
            }
            $this->bounds->validatePayload($stat);
            $this->streams->extract($zip, $payloadName, $target, (int) $stat['size']);
        } finally {
            $zip->close();
        }
    }

    private function verifyPayload(ZipArchive $zip, array $payload): void
    {
        $name = (string) ($payload['name'] ?? '');
        if (! in_array($name, ['payload/database.sql', 'payload/uploads.zip'], true)) {
            throw new RuntimeException('Payload paket backup tidak dikenal.');
        }

        $stat = $zip->statName($name);
        if (! is_array($stat)) {
            throw new RuntimeException('Payload paket backup tidak lengkap.');
        }
        $this->bounds->validatePayload($stat);
        $checksum = $this->streams->checksum($zip, $name);

        if ($checksum['bytes'] !== (int) ($payload['size'] ?? -1)
            || ! hash_equals((string) ($payload['sha256'] ?? ''), $checksum['sha256'])) {
            throw new RuntimeException('Checksum payload paket backup tidak cocok.');
        }
    }

    private function signingKey(): string
    {
        $key = (string) config('system_backup.signing_key');
        if ($key === '') {
            throw new RuntimeException('Signing key backup belum dikonfigurasi.');
        }

        return $key;
    }
}
