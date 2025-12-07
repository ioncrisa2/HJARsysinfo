<?php

namespace App\Services\Scoring;

use App\Models\Pembanding;
use App\Services\Peruntukan\PeruntukanGroupService;

class PembandingScorer
{
    protected const WEIGHTS = [
        'distance' => 1000,
        'zoning' => 10,
        'area' => 10,
        'price' => 10,
        'legal_match' => 10,
        'legal_diff' => 5,
        'road_width_perfect' => 8,
        'road_width_good' => 4,
        'road_width_poor' => 1,
        'position_match' => 5,
        'position_diff' => 2,
        'condition_match' => 5,
        'condition_diff' => 2,
    ];

    public function __construct(
        protected PeruntukanGroupService $groupService
    ) {}

    public function score(Pembanding $input, Pembanding $data): float
    {
        return collect([
            $this->scoreDistance($input, $data),
            $this->scoreZoning($input, $data),
            $this->scoreArea($input, $data),
            $this->scoreLegal($input, $data),
            $this->scoreRoadWidth($input, $data),
            $this->scorePosition($input, $data),
            $this->scoreCondition($input, $data),
            $this->scorePrice($input, $data),
        ])->sum();
    }

    protected function scoreDistance(Pembanding $input, Pembanding $data): float
    {
        $distance = $this->calculateDistance(
            $input->latitude,
            $input->longitude,
            $data->latitude,
            $data->longitude
        );

        return self::WEIGHTS['distance'] / ($distance + 1);
    }

    protected function scoreZoning(Pembanding $input, Pembanding $data): float
    {
        $zoningScore = $this->groupService->getZoningScore(
            $input->peruntukan,
            $data->peruntukan
        );

        return $zoningScore * self::WEIGHTS['zoning'];
    }

    protected function scoreArea(Pembanding $input, Pembanding $data): float
    {
        $inputArea = $input->luas_tanah + $input->luas_bangunan;
        $dataArea = $data->luas_tanah + $data->luas_bangunan;

        if ($inputArea <= 0 || $dataArea <= 0) {
            return 0;
        }

        $ratio = min($inputArea, $dataArea) / max($inputArea, $dataArea);

        return $ratio * self::WEIGHTS['area'];
    }

    protected function scoreLegal(Pembanding $input, Pembanding $data): float
    {
        $legalRanks = [
            'shm' => 3,
            'shbg' => 2,
            'lainnya' => 1,
        ];

        $inputRank = $legalRanks[strtolower($input->dokumen_tanah?->value ?? 'lainnya')] ?? 1;
        $dataRank = $legalRanks[strtolower($data->dokumen_tanah?->value ?? 'lainnya')] ?? 1;

        return $inputRank === $dataRank
            ? self::WEIGHTS['legal_match']
            : self::WEIGHTS['legal_diff'];
    }

    protected function scoreRoadWidth(Pembanding $input, Pembanding $data): int
    {
        $diff = abs(($input->lebar_jalan ?? 0) - ($data->lebar_jalan ?? 0));

        return match (true) {
            $diff <= 2 => self::WEIGHTS['road_width_perfect'],
            $diff <= 4 => self::WEIGHTS['road_width_good'],
            default => self::WEIGHTS['road_width_poor'],
        };
    }

    protected function scorePosition(Pembanding $input, Pembanding $data): int
    {
        return $input->posisi_tanah === $data->posisi_tanah
            ? self::WEIGHTS['position_match']
            : self::WEIGHTS['position_diff'];
    }

    protected function scoreCondition(Pembanding $input, Pembanding $data): int
    {
        $conditionRanks = [
            'matang' => 3,
            'belum_berkembang' => 2,
            'sawah' => 1,
        ];

        $inputRank = $conditionRanks[strtolower($input->kondisi_tanah?->value ?? 'sawah')] ?? 1;
        $dataRank = $conditionRanks[strtolower($data->kondisi_tanah?->value ?? 'sawah')] ?? 1;

        return $inputRank === $dataRank
            ? self::WEIGHTS['condition_match']
            : self::WEIGHTS['condition_diff'];
    }

    protected function scorePrice(Pembanding $input, Pembanding $data): float
    {
        if (!$input->harga || !$data->harga) {
            return 0;
        }

        $ratio = min($input->harga, $data->harga) / max($input->harga, $data->harga);

        return $ratio * self::WEIGHTS['price'];
    }

    protected function calculateDistance(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        $earthRadius = 6371000; // meters

        $lat1Rad = deg2rad($lat1);
        $lon1Rad = deg2rad($lon1);
        $lat2Rad = deg2rad($lat2);
        $lon2Rad = deg2rad($lon2);

        $dLat = $lat2Rad - $lat1Rad;
        $dLon = $lon2Rad - $lon1Rad;

        $a = sin($dLat / 2) ** 2 +
            cos($lat1Rad) * cos($lat2Rad) *
            sin($dLon / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
