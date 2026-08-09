<?php

namespace App\DTOs\Backup;

use App\Enums\BackupType;
use App\Support\BackupFileSize;

final readonly class BackupArtifact
{
    public function __construct(
        public string $id,
        public BackupType $type,
        public string $filename,
        public string $path,
        public int $size,
        public string $checksum,
        public string $createdAt,
        public array $createdBy,
        public string $origin,
        public bool $verified,
        public ?string $verifiedAt = null,
        public ?array $lastRestore = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) $data['id'],
            type: BackupType::from($data['type']),
            filename: (string) $data['filename'],
            path: (string) $data['path'],
            size: (int) $data['size'],
            checksum: (string) $data['checksum'],
            createdAt: (string) $data['created_at'],
            createdBy: (array) ($data['created_by'] ?? []),
            origin: (string) ($data['origin'] ?? 'generated'),
            verified: (bool) ($data['verified'] ?? false),
            verifiedAt: $data['verified_at'] ?? null,
            lastRestore: $data['last_restore'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'filename' => $this->filename,
            'size' => $this->size,
            'size_label' => BackupFileSize::format($this->size),
            'checksum' => $this->checksum,
            'checksum_short' => substr($this->checksum, 0, 12),
            'created_at' => $this->createdAt,
            'created_by' => $this->createdBy,
            'origin' => $this->origin,
            'verified' => $this->verified,
            'verified_at' => $this->verifiedAt,
            'last_restore' => $this->lastRestore,
        ];
    }

    public function toStorageArray(): array
    {
        return [...$this->toArray(), 'path' => $this->path];
    }

    public function verifiedAt(string $timestamp): self
    {
        return $this->copy(verified: true, verifiedAt: $timestamp);
    }

    public function withRestore(array $restore): self
    {
        return $this->copy(lastRestore: $restore);
    }

    private function copy(
        ?bool $verified = null,
        ?string $verifiedAt = null,
        ?array $lastRestore = null,
    ): self {
        return new self(
            id: $this->id,
            type: $this->type,
            filename: $this->filename,
            path: $this->path,
            size: $this->size,
            checksum: $this->checksum,
            createdAt: $this->createdAt,
            createdBy: $this->createdBy,
            origin: $this->origin,
            verified: $verified ?? $this->verified,
            verifiedAt: $verifiedAt ?? $this->verifiedAt,
            lastRestore: $lastRestore ?? $this->lastRestore,
        );
    }
}
