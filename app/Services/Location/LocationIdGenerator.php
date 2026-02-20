<?php

namespace App\Services\Location;

use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Village;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Generates sequential BPS-format location IDs for Indonesian administrative divisions.
 *
 * ID format follows BPS (Badan Pusat Statistik) coding:
 *   Province:  2 digits          e.g. "11"
 *   Regency:   4 digits          e.g. "1101"  (province + 2-digit suffix)
 *   District:  7 digits          e.g. "1101010" (regency + 3-digit suffix)
 *   Village:   10 digits         e.g. "1101010001" (district + 3-digit suffix)
 *
 * All public methods must be called inside a DB::transaction() to prevent
 * race conditions when generating IDs concurrently.
 */
class LocationIdGenerator
{
    /**
     * Generate the next available regency/city ID for a given province.
     *
     * @param  string  $provinceId  A valid 2-digit province ID.
     * @return string               A 4-digit regency ID.
     *
     * @throws InvalidArgumentException  If the province ID is invalid or does not exist.
     * @throws RuntimeException          If all possible IDs for this province are exhausted.
     */
    public function nextRegencyId(string $provinceId): string
    {
        $this->assertParentExists(Province::query(), $provinceId, 'Provinsi');

        $suffixLength = $this->suffixLengthForCompositeId($provinceId, 4, 'Kabupaten / Kota');

        return $this->nextIdForParent(
            Regency::query(),
            parentColumn: 'province_id',
            parentId: $provinceId,
            suffixLength: $suffixLength,
            label: 'Kabupaten / Kota',
        );
    }

    /**
     * Generate the next available district ID for a given regency/city.
     *
     * @param  string  $regencyId  A valid 4-digit regency ID.
     * @return string              A 7-digit district ID.
     *
     * @throws InvalidArgumentException  If the regency ID is invalid or does not exist.
     * @throws RuntimeException          If all possible IDs for this regency are exhausted.
     */
    public function nextDistrictId(string $regencyId): string
    {
        $this->assertParentExists(Regency::query(), $regencyId, 'Kabupaten / Kota');

        $suffixLength = $this->suffixLengthForCompositeId($regencyId, 7, 'Kecamatan');

        return $this->nextIdForParent(
            District::query(),
            parentColumn: 'regency_id',
            parentId: $regencyId,
            suffixLength: $suffixLength,
            label: 'Kecamatan',
        );
    }

    /**
     * Generate the next available village/kelurahan ID for a given district.
     *
     * @param  string  $districtId  A valid 7-digit district ID.
     * @return string               A 10-digit village ID.
     *
     * @throws InvalidArgumentException  If the district ID is invalid or does not exist.
     * @throws RuntimeException          If all possible IDs for this district are exhausted.
     */
    public function nextVillageId(string $districtId): string
    {
        $this->assertParentExists(District::query(), $districtId, 'Kecamatan');

        $suffixLength = $this->suffixLengthForCompositeId($districtId, 10, 'Desa / Kelurahan');

        return $this->nextIdForParent(
            Village::query(),
            parentColumn: 'district_id',
            parentId: $districtId,
            suffixLength: $suffixLength,
            label: 'Desa / Kelurahan',
        );
    }

    /**
     * Assert that a parent record with the given ID actually exists in the database.
     *
     * @throws InvalidArgumentException
     */
    private function assertParentExists(Builder $query, string $parentId, string $label): void
    {
        if (blank($parentId)) {
            throw new InvalidArgumentException("Parent id untuk {$label} wajib diisi.");
        }

        if (! $query->where('id', $parentId)->exists()) {
            throw new InvalidArgumentException("{$label} dengan id '{$parentId}' tidak ditemukan.");
        }
    }

    /**
     * Calculate the number of suffix digits needed given a parent ID and the desired total length.
     *
     * @throws InvalidArgumentException
     */
    private function suffixLengthForCompositeId(string $parentId, int $targetLength, string $label): int
    {
        $parentLength = strlen($parentId);
        $suffixLength = $targetLength - $parentLength;

        if ($suffixLength < 1) {
            throw new InvalidArgumentException("Panjang parent id untuk {$label} tidak valid.");
        }

        return $suffixLength;
    }

    /**
     * Find the next available ID by scanning existing IDs and filling the first gap.
     *
     * IMPORTANT: This method uses a pessimistic lock (SELECT ... FOR UPDATE) to prevent
     * race conditions. It must be called within a DB::transaction() block, otherwise
     * the lock has no effect and concurrent requests may generate duplicate IDs.
     *
     * @throws RuntimeException  If the ID space for this parent is exhausted.
     */
    private function nextIdForParent(
        Builder $query,
        string $parentColumn,
        string $parentId,
        int $suffixLength,
        string $label,
    ): string {
        $parentLength    = strlen($parentId);
        $expectedSuffix  = 1;
        $maxAllowedSuffix = (10 ** $suffixLength) - 1;

        // Lock matched rows so concurrent transactions wait rather than reading stale state.
        $query
            ->where($parentColumn, $parentId)
            ->orderBy('id')
            ->lockForUpdate()
            ->pluck('id')
            ->each(function ($id) use (&$expectedSuffix, $maxAllowedSuffix, $parentId, $parentLength, $suffixLength): bool|null {
                $id = (string) $id;

                if (! str_starts_with($id, $parentId)) {
                    return null; // continue
                }

                $suffix = substr($id, $parentLength);

                if ($suffix === '' || strlen($suffix) !== $suffixLength || ! ctype_digit($suffix)) {
                    return null; // continue — malformed ID, skip it
                }

                $numericSuffix = (int) $suffix;

                if ($numericSuffix < $expectedSuffix) {
                    return null; // continue — already behind our pointer, skip
                }

                if ($numericSuffix === $expectedSuffix) {
                    $expectedSuffix++;
                }

                // Return false to stop Collection::each() early once the space is full.
                if ($expectedSuffix > $maxAllowedSuffix) {
                    return false;
                }

                return null; // continue
            });

        $nextSuffix = $expectedSuffix;

        if ($nextSuffix > $maxAllowedSuffix) {
            throw new RuntimeException("Urutan kode {$label} untuk parent {$parentId} sudah penuh.");
        }

        return $parentId . str_pad((string) $nextSuffix, $suffixLength, '0', STR_PAD_LEFT);
    }
}
