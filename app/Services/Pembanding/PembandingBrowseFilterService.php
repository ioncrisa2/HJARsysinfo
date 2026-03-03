<?php

namespace App\Services\Pembanding;

use Illuminate\Database\Eloquent\Builder;

class PembandingBrowseFilterService
{
    /**
     * @return array<int, string>
     */
    public function keys(): array
    {
        return [
            'province_id',
            'regency_id',
            'district_id',
            'village_id',
            'q',
            'dari_tanggal',
            'sampai_tanggal',
            'jenis_listing_id',
            'jenis_objek_id',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            'province_id' => null,
            'regency_id' => null,
            'district_id' => null,
            'village_id' => null,
            'q' => null,
            'dari_tanggal' => null,
            'sampai_tanggal' => null,
            'jenis_listing_id' => null,
            'jenis_objek_id' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function normalize(array $filters): array
    {
        $normalized = array_merge($this->defaults(), array_intersect_key($filters, array_flip($this->keys())));

        $normalized['q'] = trim((string) ($normalized['q'] ?? ''));
        $normalized['q'] = $normalized['q'] !== '' ? $normalized['q'] : null;

        foreach ([
            'province_id',
            'regency_id',
            'district_id',
            'village_id',
            'dari_tanggal',
            'sampai_tanggal',
            'jenis_listing_id',
            'jenis_objek_id',
        ] as $key) {
            if ($normalized[$key] === '') {
                $normalized[$key] = null;
            }
        }

        if (! $normalized['province_id']) {
            $normalized['regency_id'] = null;
            $normalized['district_id'] = null;
            $normalized['village_id'] = null;
        }

        if (! $normalized['regency_id']) {
            $normalized['district_id'] = null;
            $normalized['village_id'] = null;
        }

        if (! $normalized['district_id']) {
            $normalized['village_id'] = null;
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function apply(Builder $query, array $filters): Builder
    {
        $filters = $this->normalize($filters);

        return $query
            ->when($filters['province_id'] ?? null, fn (Builder $builder, $value) => $builder->where('province_id', $value))
            ->when($filters['regency_id'] ?? null, fn (Builder $builder, $value) => $builder->where('regency_id', $value))
            ->when($filters['district_id'] ?? null, fn (Builder $builder, $value) => $builder->where('district_id', $value))
            ->when($filters['village_id'] ?? null, fn (Builder $builder, $value) => $builder->where('village_id', $value))
            ->when(
                $filters['q'] ?? null,
                fn (Builder $builder, string $search) => $builder->where('alamat_data', 'like', '%' . $search . '%')
            )
            ->when(
                $filters['dari_tanggal'] ?? null,
                fn (Builder $builder, $date) => $builder->whereDate('tanggal_data', '>=', $date),
            )
            ->when(
                $filters['sampai_tanggal'] ?? null,
                fn (Builder $builder, $date) => $builder->whereDate('tanggal_data', '<=', $date),
            )
            ->when(
                $filters['jenis_listing_id'] ?? null,
                fn (Builder $builder, $value) => $builder->where('jenis_listing_id', $value),
            )
            ->when(
                $filters['jenis_objek_id'] ?? null,
                fn (Builder $builder, $value) => $builder->where('jenis_objek_id', $value),
            );
    }
}
