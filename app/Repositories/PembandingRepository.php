<?php

namespace App\Repositories;

use App\Models\Pembanding;
use Illuminate\Support\Collection;

class PembandingRepository
{
    /**
     * Ambil kandidat pembanding berdasarkan bounding box dan hitung sql_distance.
     */
    public function getGeoCandidates(Pembanding $input, int $limit = 300): Collection
    {
        $lat = $input->latitude;
        $lng = $input->longitude;

        $earthRadius = 6371000;
        $maxDistance = 10000; // meters

        // Convert radius to degrees
        $latRange = $maxDistance / 111320;
        $lngRange = $maxDistance / (111320 * cos(deg2rad($lat)));

        $minLat = $lat - $latRange;
        $maxLat = $lat + $latRange;
        $minLng = $lng - $lngRange;
        $maxLng = $lng + $lngRange;

        return Pembanding::selectRaw("
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
        ->where('district_id', $input->district_id)
        ->whereBetween('latitude', [$minLat, $maxLat])
        ->whereBetween('longitude', [$minLng, $maxLng])
        ->orderBy('sql_distance')
        ->limit($limit)
        ->get();
    }
}
