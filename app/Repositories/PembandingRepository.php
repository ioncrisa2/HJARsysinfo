<?php

namespace App\Repositories;

use App\Models\Pembanding;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PembandingRepository
{
    /**
     * Ambil kandidat pembanding berdasarkan bounding box dan hitung sql_distance.
     */
    public function getGeoCandidates(
        Pembanding $input,
        int $limit = 300,
        array $allowedPeruntukan = [],
        ?int $districtId = null,
        ?int $regencyId = null,
        ?float $minTotalArea = null,
        ?float $maxTotalArea = null,
    ): Collection {
        $lat = $input->latitude;
        $lng = $input->longitude;

        $districtId ??= $input->district_id;
        $regencyId ??= $input->regency_id;

        $earthRadius = 6371000;
        $maxDistance = 10000; // meters

        // Convert radius to degrees
        $latRange = $maxDistance / 111320;
        $lngRange = $maxDistance / (111320 * cos(deg2rad($lat)));

        $minLat = $lat - $latRange;
        $maxLat = $lat + $latRange;
        $minLng = $lng - $lngRange;
        $maxLng = $lng + $lngRange;

        return Pembanding::selectRaw(
            "
                data_pembanding.*,
                ({$earthRadius} * ACOS(
                    LEAST(
                        1.0,
                        COS(RADIANS(?)) * COS(RADIANS(latitude)) *
                        COS(RADIANS(longitude) - RADIANS(?)) +
                        SIN(RADIANS(?)) * SIN(RADIANS(latitude))
                    )
                )) AS sql_distance
            ",
            [$lat, $lng, $lat]
        )
            ->when($districtId, function ($query) use ($districtId) {
                $query->where('district_id', $districtId);
            })
            ->when(! $districtId && $regencyId, function ($query) use ($regencyId) {
                $query->where('regency_id', $regencyId);
            })
            ->when(! empty($allowedPeruntukan), function ($query) use ($allowedPeruntukan) {
                $query->whereIn('peruntukan', $allowedPeruntukan);
            })
            ->when($input->getKey(), function ($query) use ($input) {
                $query->whereKeyNot($input->getKey());
            })
            ->when($minTotalArea !== null || $maxTotalArea !== null, function ($query) use ($minTotalArea, $maxTotalArea) {
                $min = $minTotalArea ?? 0;
                $max = $maxTotalArea ?? PHP_FLOAT_MAX;

                $query->whereBetween(
                    DB::raw('COALESCE(luas_tanah, 0) + COALESCE(luas_bangunan, 0)'),
                    [$min, $max]
                );
            })
            ->whereBetween('latitude', [$minLat, $maxLat])
            ->whereBetween('longitude', [$minLng, $maxLng])
            ->orderBy('sql_distance')
            ->limit($limit)
            ->get();
    }
}
