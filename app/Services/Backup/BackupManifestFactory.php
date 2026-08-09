<?php

namespace App\Services\Backup;

use App\Enums\BackupType;
use App\Models\User;

final class BackupManifestFactory
{
    public function payload(string $name, string $path): array
    {
        return [
            'name' => $name,
            'source' => $path,
            'size' => filesize($path),
            'sha256' => hash_file('sha256', $path),
        ];
    }

    public function make(
        string $id,
        BackupType $type,
        array $payloads,
        User $user,
        string $createdAt,
    ): array {
        return [
            'schema_version' => 1,
            'id' => $id,
            'type' => $type->value,
            'created_at' => $createdAt,
            'created_by' => ['id' => $user->id, 'name' => $user->name],
            'application' => (string) config('app.name'),
            'payloads' => collect($payloads)->map(fn (array $item) => [
                'name' => $item['name'],
                'size' => $item['size'],
                'sha256' => $item['sha256'],
            ])->all(),
        ];
    }
}
