<?php

namespace App\Services\Geo;

class GeoDistanceCalculator
{
    protected const EARTH_RADIUS_METERS = 6371000;

    /**
     * Get SQL expression for calculating distance using Haversine formula
     */
    public function getSqlExpression(float $lat, float $lng): string
    {
        $radius = self::EARTH_RADIUS_METERS;

        return "
            ({$radius} * ACOS(
                LEAST(
                    1.0,
                    COS(RADIANS(?)) * COS(RADIANS(latitude)) *
                    COS(RADIANS(longitude) - RADIANS(?)) +
                    SIN(RADIANS(?)) * SIN(RADIANS(latitude))
                )
            ))
        ";
    }

    /**
     * Calculate distance between two points in meters
     */
    public function calculate(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): float {
        $lat1Rad = deg2rad($lat1);
        $lng1Rad = deg2rad($lng1);
        $lat2Rad = deg2rad($lat2);
        $lng2Rad = deg2rad($lng2);

        $dLat = $lat2Rad - $lat1Rad;
        $dLng = $lng2Rad - $lng1Rad;

        $a = sin($dLat / 2) ** 2 +
            cos($lat1Rad) * cos($lat2Rad) *
            sin($dLng / 2) ** 2;

        return self::EARTH_RADIUS_METERS * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
