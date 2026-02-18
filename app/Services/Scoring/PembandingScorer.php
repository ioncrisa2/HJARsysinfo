<?php

namespace App\Services\Scoring;

use App\Enums\Peruntukan;
use App\Models\Pembanding;
use App\Services\Peruntukan\PeruntukanGroupService;
use BackedEnum;

class PembandingScorer
{
    protected const WEIGHTS = [
        'distance' => 25,
        'zoning' => 20,
        'area' => 15,
        'price' => 15,
        'legal_match' => 10,
        'legal_diff' => 6,
        'road_width_perfect' => 8,
        'road_width_good' => 4,
        'road_width_poor' => 0,
        'position_match' => 4,
        'position_diff' => 2,
        'condition_match' => 3,
        'condition_diff' => 1,
    ];

    protected const DISTANCE_DECAY_METERS = 2500;

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
        $distance = is_numeric($data->distance ?? null)
            ? (float) $data->distance
            : $this->calculateDistance(
                (float) $input->latitude,
                (float) $input->longitude,
                (float) $data->latitude,
                (float) $data->longitude
            );

        return self::WEIGHTS['distance'] * exp(-$distance / self::DISTANCE_DECAY_METERS);
    }

    protected function scoreZoning(Pembanding $input, Pembanding $data): float
    {
        $inputPeruntukan = $this->resolvePeruntukanEnum($input);
        $dataPeruntukan = $this->resolvePeruntukanEnum($data);

        if (! $inputPeruntukan || ! $dataPeruntukan) {
            return 0;
        }

        $zoningScore = $this->groupService->getZoningScore(
            $inputPeruntukan,
            $dataPeruntukan
        );

        $normalized = match ($zoningScore) {
            3 => 1.0,
            1 => 0.35,
            default => 0.0,
        };

        return $normalized * self::WEIGHTS['zoning'];
    }

    protected function scoreArea(Pembanding $input, Pembanding $data): float
    {
        $inputArea = (float) ($input->luas_tanah ?? 0) + (float) ($input->luas_bangunan ?? 0);
        $dataArea = (float) ($data->luas_tanah ?? 0) + (float) ($data->luas_bangunan ?? 0);

        if ($inputArea <= 0 || $dataArea <= 0) {
            return 0;
        }

        $ratio = min($inputArea, $dataArea) / max($inputArea, $dataArea);

        return $ratio * self::WEIGHTS['area'];
    }

    protected function scoreLegal(Pembanding $input, Pembanding $data): float
    {
        $legalRanks = [
            'sertifikat_hak_milik' => 4,
            'sertifikat_hak_guna_bangunan' => 3,
            'sertifikat_hak_guna_usaha' => 3,
            'akta_jual_beli' => 2,
            'girik' => 1,
            'petok_desa' => 1,
            'surat_camat' => 1,
            'peta_bidang_tanah' => 1,
            'lainnya' => 0,
        ];

        $inputSlug = $this->resolveDokumenSlug($input);
        $dataSlug = $this->resolveDokumenSlug($data);

        if (! $inputSlug || ! $dataSlug) {
            return 0;
        }

        $inputRank = $legalRanks[$inputSlug] ?? 0;
        $dataRank = $legalRanks[$dataSlug] ?? 0;

        $diff = abs($inputRank - $dataRank);

        return match (true) {
            $diff === 0 => self::WEIGHTS['legal_match'],
            $diff === 1 => self::WEIGHTS['legal_diff'],
            default => 0.0,
        };
    }

    protected function scoreRoadWidth(Pembanding $input, Pembanding $data): int
    {
        if (! isset($input->lebar_jalan, $data->lebar_jalan)) {
            return 0;
        }

        $diff = abs((float) $input->lebar_jalan - (float) $data->lebar_jalan);

        return match (true) {
            $diff <= 2 => self::WEIGHTS['road_width_perfect'],
            $diff <= 4 => self::WEIGHTS['road_width_good'],
            default => self::WEIGHTS['road_width_poor'],
        };
    }

    protected function scorePosition(Pembanding $input, Pembanding $data): int
    {
        $inputSlug = $this->resolvePosisiSlug($input);
        $dataSlug = $this->resolvePosisiSlug($data);

        if (! $inputSlug || ! $dataSlug) {
            return 0;
        }

        return $inputSlug === $dataSlug
            ? self::WEIGHTS['position_match']
            : self::WEIGHTS['position_diff'];
    }

    protected function scoreCondition(Pembanding $input, Pembanding $data): int
    {
        $conditionRanks = [
            'matang' => 3,
            'rawa' => 2,
            'belum_berkembang' => 2,
            'sawah' => 1,
            'lainnya' => 0,
        ];

        $inputSlug = $this->resolveKondisiSlug($input);
        $dataSlug = $this->resolveKondisiSlug($data);

        if (! $inputSlug || ! $dataSlug) {
            return 0;
        }

        $inputRank = $conditionRanks[$inputSlug] ?? 0;
        $dataRank = $conditionRanks[$dataSlug] ?? 0;

        $diff = abs($inputRank - $dataRank);

        return match (true) {
            $diff === 0 => self::WEIGHTS['condition_match'],
            $diff === 1 => self::WEIGHTS['condition_diff'],
            default => 0,
        };
    }

    protected function scorePrice(Pembanding $input, Pembanding $data): float
    {
        $inputUnitPrice = $this->resolveUnitPrice($input);
        $dataUnitPrice = $this->resolveUnitPrice($data);

        if (! $inputUnitPrice || ! $dataUnitPrice) {
            return 0;
        }

        $ratio = min($inputUnitPrice, $dataUnitPrice) / max($inputUnitPrice, $dataUnitPrice);

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

    protected function resolvePeruntukanEnum(Pembanding $item): ?Peruntukan
    {
        $raw = $item->peruntukanRef?->slug ?? ($item->peruntukan ?? null);

        if ($raw instanceof Peruntukan) {
            return $raw;
        }

        if ($raw instanceof BackedEnum) {
            $raw = $raw->value;
        }

        return is_string($raw) ? Peruntukan::tryFrom($raw) : null;
    }

    protected function resolveDokumenSlug(Pembanding $item): ?string
    {
        $raw = $item->dokumenTanah?->slug ?? ($item->dokumen_tanah ?? null);

        if ($raw instanceof BackedEnum) {
            $raw = $raw->value;
        }

        return is_string($raw) ? strtolower($raw) : null;
    }

    protected function resolvePosisiSlug(Pembanding $item): ?string
    {
        $raw = $item->posisiTanah?->slug ?? ($item->posisi_tanah ?? null);

        if ($raw instanceof BackedEnum) {
            $raw = $raw->value;
        }

        return is_string($raw) ? strtolower($raw) : null;
    }

    protected function resolveKondisiSlug(Pembanding $item): ?string
    {
        $raw = $item->kondisiTanah?->slug ?? ($item->kondisi_tanah ?? null);

        if ($raw instanceof BackedEnum) {
            $raw = $raw->value;
        }

        return is_string($raw) ? strtolower($raw) : null;
    }

    protected function resolveUnitPrice(Pembanding $item): ?float
    {
        $price = (float) ($item->harga ?? 0);
        $area = (float) ($item->luas_tanah ?? 0) + (float) ($item->luas_bangunan ?? 0);

        if ($price <= 0 || $area <= 0) {
            return null;
        }

        return $price / $area;
    }
}
