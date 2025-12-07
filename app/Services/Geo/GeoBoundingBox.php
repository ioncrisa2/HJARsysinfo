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

    /**
     * Check if a point is within the bounding box
     */
    public function contains(array $bounds, float $lat, float $lng): bool
    {
        return $lat >= $bounds['min_lat'] &&
            $lat <= $bounds['max_lat'] &&
            $lng >= $bounds['min_lng'] &&
            $lng <= $bounds['max_lng'];
    }
}
