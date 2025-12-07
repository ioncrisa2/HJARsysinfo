<?php

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

    protected function filterByPeruntukan(Builder $query, string $peruntukan): void
    {
        $query->where('peruntukan', $peruntukan);
    }

    protected function filterByJenisObjek(Builder $query, string $jenisObjek): void
    {
        $query->where('jenis_objek', $jenisObjek);
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
