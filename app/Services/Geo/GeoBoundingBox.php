<?php

namespace App\Services\Geo;

class GeoBoundingBox
{
    protected const METERS_PER_DEGREE_LAT = 111320;

    /**
     * Calculate bounding box coordinates for a given center point and radius
     */
    public function calculate(float $lat, float $lng, float $radiusMeters): array
    {
        $latRange = $radiusMeters / self::METERS_PER_DEGREE_LAT;
        $lngRange = $radiusMeters / (self::METERS_PER_DEGREE_LAT * cos(deg2rad($lat)));

        return [
            'min_lat' => $lat - $latRange,
            'max_lat' => $lat + $latRange,
            'min_lng' => $lng - $lngRange,
            'max_lng' => $lng + $lngRange,
        ];
    }

}
