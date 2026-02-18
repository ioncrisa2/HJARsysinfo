<?php

namespace App\Repositories;

use App\Models\Pembanding;
use App\Services\Geo\GeoBoundingBox;
use App\Services\Geo\GeoDistanceCalculator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
    ): Collection {
        $effectiveRadius = $radiusMeters ?? self::DEFAULT_RADIUS_METERS;

        $bounds = $this->boundingBox->calculate(
            $input->latitude,
            $input->longitude,
            $effectiveRadius
        );

        $query = $this->buildBaseQuery($input, $bounds, $effectiveRadius);

        $this->applyLocationFilters($query, $districtId ?? $input->district_id, $regencyId ?? $input->regency_id);
        $this->applyPeruntukanFilter($query, $allowedPeruntukan);
        $this->applyAreaFilter($query, $minTotalArea, $maxTotalArea);
        $this->excludeInput($query, $input);

        return $query
            ->orderBy('distance')
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

    protected function applyAreaFilter(Builder $query, ?float $minTotalArea, ?float $maxTotalArea): void
    {
        if ($minTotalArea === null && $maxTotalArea === null) {
            return;
        }

        $min = $minTotalArea ?? 0;
        $max = $maxTotalArea ?? PHP_FLOAT_MAX;

        $query->whereBetween(
            DB::raw('COALESCE(luas_tanah, 0) + COALESCE(luas_bangunan, 0)'),
            [$min, $max]
        );
    }

    protected function excludeInput(Builder $query, Pembanding $input): void
    {
        if (!$input->getKey()) {
            return;
        }

        $query->whereKeyNot($input->getKey());
    }
}
