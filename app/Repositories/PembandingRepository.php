<?php

namespace App\Repositories;

use App\Models\Pembanding;
use App\Services\Geo\GeoBoundingBox;
use App\Services\Geo\GeoDistanceCalculator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PembandingRepository
{
    protected const DEFAULT_LIMIT = 300;

    protected const DEFAULT_RADIUS_METERS = 10000;

    public function __construct(
        protected GeoDistanceCalculator $distanceCalculator,
        protected GeoBoundingBox $boundingBox
    ) {}

    public function getGeoCandidates(
        Pembanding $input,
        int $limit = self::DEFAULT_LIMIT,
        array $allowedPeruntukan = [],
        ?string $districtId = null,
        ?string $regencyId = null,
        ?float $minTotalArea = null,
        ?float $maxTotalArea = null,
        ?int $radiusMeters = null,
        bool $useInputLocation = true,
        ?string $marketBasis = null,
        ?string $referenceDate = null,
        string $areaMetric = 'total',
        array $excludeIds = [],
        ?string $excludeDistrictId = null,
        ?string $excludeRegencyId = null,
    ): Collection {
        $effectiveRadius = $radiusMeters ?? self::DEFAULT_RADIUS_METERS;

        $bounds = $this->boundingBox->calculate(
            $input->latitude,
            $input->longitude,
            $effectiveRadius
        );

        $query = $this->buildBaseQuery($input, $bounds, $effectiveRadius);

        $effectiveDistrict = $useInputLocation ? ($districtId ?? $input->district_id) : $districtId;
        $effectiveRegency = $useInputLocation ? ($regencyId ?? $input->regency_id) : $regencyId;

        $this->applyLocationFilters($query, $effectiveDistrict, $effectiveRegency);
        $this->applyPeruntukanFilter($query, $allowedPeruntukan);
        $this->applyMarketBasisFilter($query, $marketBasis);
        $this->applyReferenceDateFilter($query, $referenceDate);
        $this->applyAreaFilter($query, $minTotalArea, $maxTotalArea, $areaMetric);
        $this->excludeInput($query, $input);
        $this->excludeIds($query, $excludeIds);
        $this->excludeLocations($query, $excludeDistrictId, $excludeRegencyId);

        return $query
            ->orderBy('distance')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    protected function buildBaseQuery(Pembanding $input, array $bounds, int $radiusMeters): Builder
    {
        $distanceExpression = $this->distanceCalculator->getSqlExpression(
            $input->latitude,
            $input->longitude
        );

        $bindings = [(float) $input->latitude, (float) $input->longitude, (float) $input->latitude];

        return Pembanding::selectRaw(
            "data_pembanding.*, {$distanceExpression} AS distance",
            $bindings
        )
            ->whereBetween('latitude', [$bounds['min_lat'], $bounds['max_lat']])
            ->whereBetween('longitude', [$bounds['min_lng'], $bounds['max_lng']])
            ->whereRaw(
                "{$distanceExpression} <= ?",
                array_merge($bindings, [$radiusMeters])
            );
    }

    protected function applyLocationFilters(Builder $query, ?string $districtId, ?string $regencyId): void
    {
        if ($districtId) {
            $query->where('district_id', $districtId);

            return;
        }

        if ($regencyId) {
            $query->where('regency_id', $regencyId);
        }
    }

    protected function applyPeruntukanFilter(Builder $query, array $allowedPeruntukan): void
    {
        if (empty($allowedPeruntukan)) {
            return;
        }

        $query->whereHas('peruntukanRef', function (Builder $relationQuery) use ($allowedPeruntukan) {
            $relationQuery->whereIn('slug', $allowedPeruntukan);
        });
    }

    protected function applyMarketBasisFilter(Builder $query, ?string $marketBasis): void
    {
        $slugs = match ($marketBasis) {
            'sale' => ['penawaran', 'transaksi'],
            'rent' => ['sewa'],
            default => [],
        };

        if ($slugs === []) {
            return;
        }

        $query->whereHas('jenisListing', function (Builder $relationQuery) use ($slugs) {
            $relationQuery->whereIn('slug', $slugs);
        });
    }

    protected function applyReferenceDateFilter(Builder $query, ?string $referenceDate): void
    {
        if ($referenceDate) {
            $query->whereDate('tanggal_data', '<=', $referenceDate);
        }
    }

    protected function applyAreaFilter(
        Builder $query,
        ?float $minTotalArea,
        ?float $maxTotalArea,
        string $areaMetric,
    ): void {
        if ($minTotalArea === null && $maxTotalArea === null) {
            return;
        }

        $expression = match ($areaMetric) {
            'land' => 'COALESCE(luas_tanah, 0)',
            'building' => 'COALESCE(luas_bangunan, 0)',
            default => 'COALESCE(luas_tanah, 0) + COALESCE(luas_bangunan, 0)',
        };

        if ($minTotalArea !== null) {
            $query->whereRaw(
                "{$expression} >= CAST(? AS DECIMAL(18, 4))",
                [$minTotalArea],
            );
        }

        if ($maxTotalArea !== null) {
            $query->whereRaw(
                "{$expression} <= CAST(? AS DECIMAL(18, 4))",
                [$maxTotalArea],
            );
        }
    }

    protected function excludeInput(Builder $query, Pembanding $input): void
    {
        if (! $input->getKey()) {
            return;
        }

        $query->whereKeyNot($input->getKey());
    }

    protected function excludeIds(Builder $query, array $ids): void
    {
        if ($ids !== []) {
            $query->whereNotIn($query->getModel()->getQualifiedKeyName(), $ids);
        }
    }

    protected function excludeLocations(
        Builder $query,
        ?string $districtId,
        ?string $regencyId,
    ): void {
        if ($districtId) {
            $query->where('district_id', '!=', $districtId);
        }

        if ($regencyId) {
            $query->where('regency_id', '!=', $regencyId);
        }
    }
}
