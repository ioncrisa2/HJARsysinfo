<?php

namespace App\Repositories;

use App\Models\Pembanding;
use App\Services\Geo\GeoBoundingBox;
use App\Services\Geo\GeoDistanceCalculator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PembandingQueryBuilder
{
    protected Builder $query;
    protected ?array $bounds = null;

    public function __construct(
        protected GeoDistanceCalculator $distanceCalculator,
        protected GeoBoundingBox $boundingBox
    ) {
        $this->query = Pembanding::query();
    }

    public function withinRadius(float $lat, float $lng, int $radiusMeters): self
    {
        $this->bounds = $this->boundingBox->calculate($lat, $lng, $radiusMeters);

        $distanceExpression = $this->distanceCalculator->getSqlExpression($lat, $lng);

        $this->query->selectRaw(
            "data_pembanding.*, {$distanceExpression} AS sql_distance",
            [$lat, $lng, $lat]
        )
            ->whereBetween('latitude', [$this->bounds['min_lat'], $this->bounds['max_lat']])
            ->whereBetween('longitude', [$this->bounds['min_lng'], $this->bounds['max_lng']]);

        return $this;
    }

    public function inDistrict(?int $districtId): self
    {
        if ($districtId) {
            $this->query->where('district_id', $districtId);
        }

        return $this;
    }

    public function inRegency(?int $regencyId): self
    {
        if ($regencyId) {
            $this->query->where('regency_id', $regencyId);
        }

        return $this;
    }

    public function withPeruntukan(array $peruntukanValues): self
    {
        if (!empty($peruntukanValues)) {
            $this->query->whereIn('peruntukan', $peruntukanValues);
        }

        return $this;
    }

    public function withTotalAreaBetween(?float $min, ?float $max): self
    {
        if ($min === null && $max === null) {
            return $this;
        }

        $this->query->whereBetween(
            DB::raw('COALESCE(luas_tanah, 0) + COALESCE(luas_bangunan, 0)'),
            [$min ?? 0, $max ?? PHP_FLOAT_MAX]
        );

        return $this;
    }

    public function excluding(Pembanding $pembanding): self
    {
        if ($pembanding->getKey()) {
            $this->query->whereKeyNot($pembanding->getKey());
        }

        return $this;
    }

    public function orderByDistance(): self
    {
        $this->query->orderBy('sql_distance');
        return $this;
    }

    public function get(int $limit = 300)
    {
        return $this->query->limit($limit)->get();
    }

    public function getQuery(): Builder
    {
        return $this->query;
    }
}
