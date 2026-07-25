<?php

namespace App\Services\Scoring;

use App\Models\Pembanding;
use BackedEnum;

class ScoringAttributeResolver
{
    public function peruntukan(Pembanding $item): ?string
    {
        return $this->slug($item, 'peruntukanRef', 'peruntukan');
    }

    public function objectType(Pembanding $item): ?string
    {
        return $this->slug($item, 'jenisObjek', 'jenis_objek');
    }

    public function legal(Pembanding $item): ?string
    {
        return $this->slug($item, 'dokumenTanah', 'dokumen_tanah');
    }

    public function position(Pembanding $item): ?string
    {
        return $this->slug($item, 'posisiTanah', 'posisi_tanah');
    }

    public function condition(Pembanding $item): ?string
    {
        return $this->slug($item, 'kondisiTanah', 'kondisi_tanah');
    }

    public function listing(Pembanding $item): ?string
    {
        return $this->slug($item, 'jenisListing', 'jenis_listing');
    }

    public function marketBasis(Pembanding $item): ?string
    {
        $explicit = $this->normalizedString($item->getAttribute('market_basis'));

        if ($explicit) {
            return $explicit;
        }

        return match ($this->listing($item)) {
            'sewa' => 'rent',
            'penawaran', 'transaksi' => 'sale',
            default => null,
        };
    }

    public function evidenceType(Pembanding $item): string
    {
        $explicit = $this->normalizedString($item->getAttribute('evidence_type'));

        return $explicit ?? match ($this->listing($item)) {
            'transaksi' => 'transaction',
            'penawaran' => 'offer',
            default => 'unknown',
        };
    }

    private function slug(Pembanding $item, string $relation, string $legacyAttribute): ?string
    {
        $related = $item->relationLoaded($relation) ? $item->getRelation($relation) : null;
        $raw = $related?->slug ?? $item->getAttribute($legacyAttribute);

        if ($raw instanceof BackedEnum) {
            $raw = $raw->value;
        }

        return $this->normalizedString($raw);
    }

    private function normalizedString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = strtolower(trim($value));

        return $value === '' ? null : $value;
    }
}
