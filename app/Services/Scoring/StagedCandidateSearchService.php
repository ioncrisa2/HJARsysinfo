<?php

namespace App\Services\Scoring;

use App\Models\Pembanding;
use App\Repositories\PembandingRepository;
use Illuminate\Support\Collection;

class StagedCandidateSearchService
{
    public function __construct(
        private readonly PembandingRepository $repository,
        private readonly ScoringAttributeResolver $resolver,
        private readonly AdministrativeAreaResolver $areas,
    ) {}

    public function search(
        Pembanding $input,
        int $limit,
        array $allowedPeruntukan,
        ?float $minTotalArea = null,
        ?float $maxTotalArea = null,
        ?int $radiusMeters = null,
        int $priority = 99,
        bool $fallback = false,
        string $areaMetric = 'total',
    ): Collection {
        $regencyId = $this->areas->regencyId($input);

        if ($fallback) {
            return $this->fetch(
                $input,
                $limit,
                $allowedPeruntukan,
                $minTotalArea,
                $maxTotalArea,
                $radiusMeters,
                null,
                null,
                [],
                $areaMetric,
                null,
                null,
            )->map(fn (Pembanding $candidate) => $this->tag(
                $input,
                $candidate,
                $priority,
                true,
                $regencyId,
            ));
        }

        $districtTarget = $input->district_id
            ? max(1, (int) ceil($limit * (float) config(
                'pembanding_scoring.candidate_pool.district_share',
                0.4,
            )))
            : 0;
        $regencyTarget = $regencyId
            ? max(1, (int) ceil($limit * (float) config(
                'pembanding_scoring.candidate_pool.regency_share',
                0.3,
            )))
            : 0;
        $results = collect();

        if ($districtTarget > 0) {
            $results = $results->merge($this->fetch(
                $input,
                $districtTarget,
                $allowedPeruntukan,
                $minTotalArea,
                $maxTotalArea,
                $radiusMeters,
                $input->district_id,
                null,
                [],
                $areaMetric,
                null,
                null,
            ));
        }

        if ($regencyTarget > 0) {
            $results = $results->merge($this->fetch(
                $input,
                $regencyTarget,
                $allowedPeruntukan,
                $minTotalArea,
                $maxTotalArea,
                $radiusMeters,
                null,
                $regencyId,
                $results->pluck('id')->all(),
                $areaMetric,
                $input->district_id,
                null,
            ));
        }

        $results = $results->unique('id')->values();
        $remaining = max(0, $limit - $results->count());

        if ($remaining > 0) {
            $results = $results->merge($this->fetch(
                $input,
                $remaining,
                $allowedPeruntukan,
                $minTotalArea,
                $maxTotalArea,
                $radiusMeters,
                null,
                null,
                $results->pluck('id')->all(),
                $areaMetric,
                null,
                $regencyId,
            ));
        }

        $results = $results->unique('id')->values();
        $remaining = max(0, $limit - $results->count());

        if ($remaining > 0) {
            $results = $results->merge($this->fetch(
                $input,
                $remaining,
                $allowedPeruntukan,
                $minTotalArea,
                $maxTotalArea,
                $radiusMeters,
                null,
                null,
                $results->pluck('id')->all(),
                $areaMetric,
                null,
                null,
            ));
        }

        return $results->unique('id')->take($limit)->values()->map(
            fn (Pembanding $candidate) => $this->tag(
                $input,
                $candidate,
                $priority,
                false,
                $regencyId,
            ),
        );
    }

    private function fetch(
        Pembanding $input,
        int $limit,
        array $allowedPeruntukan,
        ?float $minTotalArea,
        ?float $maxTotalArea,
        ?int $radiusMeters,
        ?string $districtId,
        ?string $regencyId,
        array $excludeIds,
        string $areaMetric,
        ?string $excludeDistrictId,
        ?string $excludeRegencyId,
    ): Collection {
        return $this->repository->getGeoCandidates(
            input: $input,
            limit: $limit,
            allowedPeruntukan: $allowedPeruntukan,
            districtId: $districtId,
            regencyId: $regencyId,
            minTotalArea: $minTotalArea,
            maxTotalArea: $maxTotalArea,
            radiusMeters: $radiusMeters,
            useInputLocation: false,
            marketBasis: $this->resolver->marketBasis($input),
            referenceDate: $input->getAttribute('reference_date'),
            areaMetric: $areaMetric,
            excludeIds: $excludeIds,
            excludeDistrictId: $excludeDistrictId,
            excludeRegencyId: $excludeRegencyId,
        );
    }

    private function tag(
        Pembanding $input,
        Pembanding $candidate,
        int $priority,
        bool $fallback,
        ?string $regencyId,
    ): Pembanding {
        $candidate->priority_rank = $priority;
        $candidate->is_fallback = $fallback;
        $candidate->retrieval_stage = $this->stage(
            $input,
            $candidate,
            $fallback,
            $regencyId,
        );

        return $candidate;
    }

    private function stage(
        Pembanding $input,
        Pembanding $candidate,
        bool $fallback,
        ?string $regencyId,
    ): string {
        if ($fallback) {
            return 'cross_peruntukan_fallback';
        }

        if ($input->district_id && $candidate->district_id === $input->district_id) {
            return 'district';
        }

        if ($regencyId && $candidate->regency_id === $regencyId) {
            return 'regency';
        }

        return 'radius';
    }
}
