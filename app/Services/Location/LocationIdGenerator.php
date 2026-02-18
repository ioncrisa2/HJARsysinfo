<?php

namespace App\Services\Location;

use App\Models\District;
use App\Models\Regency;
use App\Models\Village;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use RuntimeException;

class LocationIdGenerator
{
    public function nextRegencyId(string $provinceId): string
    {
        return $this->nextIdForParent(
            Regency::query(),
            parentColumn: 'province_id',
            parentId: $provinceId,
            suffixLength: 2,
            label: 'Kabupaten / Kota',
        );
    }

    public function nextDistrictId(string $regencyId): string
    {
        return $this->nextIdForParent(
            District::query(),
            parentColumn: 'regency_id',
            parentId: $regencyId,
            suffixLength: 2,
            label: 'Kecamatan',
        );
    }

    public function nextVillageId(string $districtId): string
    {
        return $this->nextIdForParent(
            Village::query(),
            parentColumn: 'district_id',
            parentId: $districtId,
            suffixLength: 4,
            label: 'Desa / Kelurahan',
        );
    }

    private function nextIdForParent(
        Builder $query,
        string $parentColumn,
        string $parentId,
        int $suffixLength,
        string $label,
    ): string {
        if (blank($parentId)) {
            throw new InvalidArgumentException("Parent id untuk {$label} wajib diisi.");
        }

        $parentLength = strlen($parentId);
        $expectedSuffix = 1;
        $maxAllowedSuffix = (10 ** $suffixLength) - 1;

        $query
            ->where($parentColumn, $parentId)
            ->orderBy('id')
            ->pluck('id')
            ->each(function ($id) use (&$expectedSuffix, $maxAllowedSuffix, $parentId, $parentLength, $suffixLength): void {
                $id = (string) $id;

                if (! str_starts_with($id, $parentId)) {
                    return;
                }

                $suffix = substr($id, $parentLength);

                if ($suffix === '' || strlen($suffix) !== $suffixLength || ! ctype_digit($suffix)) {
                    return;
                }

                $numericSuffix = (int) $suffix;

                if ($numericSuffix < $expectedSuffix) {
                    return;
                }

                if ($numericSuffix === $expectedSuffix) {
                    $expectedSuffix++;
                }

                if ($expectedSuffix > $maxAllowedSuffix) {
                    return;
                }
            });

        $nextSuffix = $expectedSuffix;

        if ($nextSuffix > $maxAllowedSuffix) {
            throw new RuntimeException("Urutan kode {$label} untuk parent {$parentId} sudah penuh.");
        }

        return $parentId . str_pad((string) $nextSuffix, $suffixLength, '0', STR_PAD_LEFT);
    }
}
