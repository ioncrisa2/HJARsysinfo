<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

class PembandingFilterService
{
    public function apply(Builder $query, array $filters): Builder
    {
        if (isset($filters['district_id'])) {
            $this->filterByDistrict($query, $filters['district_id']);
        }

        if (isset($filters['peruntukan'])) {
            $this->filterByPeruntukan($query, $filters['peruntukan']);
        }

        if (isset($filters['jenis_objek'])) {
            $this->filterByJenisObjek($query, $filters['jenis_objek']);
        }

        if (isset($filters['min_harga']) || isset($filters['max_harga'])) {
            $this->filterByPriceRange(
                $query,
                $filters['min_harga'] ?? null,
                $filters['max_harga'] ?? null
            );
        }

        return $query;
    }

    protected function filterByDistrict(Builder $query, string $districtId): void
    {
        $query->where('district_id', $districtId);
    }

    protected function filterByPeruntukan(Builder $query, string $peruntukanSlug): void
    {
        // Filter using relationship and slug
        $query->whereHas('peruntukanRef', function ($q) use ($peruntukanSlug) {
            $q->where('slug', $peruntukanSlug);
        });
    }

    protected function filterByJenisObjek(Builder $query, string $jenisObjekSlug): void
    {
        // Filter using relationship and slug
        $query->whereHas('jenisObjek', function ($q) use ($jenisObjekSlug) {
            $q->where('slug', $jenisObjekSlug);
        });
    }

    protected function filterByPriceRange(Builder $query, ?float $min, ?float $max): void
    {
        if ($min !== null) {
            $query->where('harga', '>=', $min);
        }

        if ($max !== null) {
            $query->where('harga', '<=', $max);
        }
    }
}
