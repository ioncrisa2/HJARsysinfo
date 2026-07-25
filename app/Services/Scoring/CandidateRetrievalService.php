<?php

namespace App\Services\Scoring;

use App\Models\Pembanding;
use App\Services\Peruntukan\PeruntukanGroupService;
use Illuminate\Support\Collection;

class CandidateRetrievalService
{
    public function __construct(
        private readonly ScoringAttributeResolver $resolver,
        private readonly ScoringProfile $profile,
        private readonly PeruntukanGroupService $groups,
        private readonly StagedCandidateSearchService $search,
        private readonly GudangCandidateRetrievalService $gudang,
    ) {}

    public function retrieve(
        Pembanding $input,
        int $resultLimit,
        ?int $radiusMeters,
    ): Collection {
        $poolLimit = $this->targetPoolSize($resultLimit);
        $peruntukan = $this->resolver->peruntukan($input);

        if ($peruntukan === 'gudang') {
            return $this->gudang->retrieve($input, $poolLimit, $radiusMeters);
        }

        $allowed = array_values(array_unique(array_filter(array_merge(
            $this->groups->getAllowedPeruntukan($peruntukan),
            $peruntukan ? $this->profile->configuredSubstitutions(
                'peruntukan_similarity',
                $peruntukan,
            ) : [],
        ))));
        $candidates = $this->search->search(
            $input,
            $poolLimit,
            $allowed,
            radiusMeters: $radiusMeters,
        );

        if ($candidates->isNotEmpty() || $peruntukan === 'ruko') {
            return $candidates;
        }

        return $this->search->search(
            $input,
            $poolLimit,
            [],
            radiusMeters: $radiusMeters,
            fallback: true,
        );
    }

    public function targetPoolSize(int $resultLimit): int
    {
        $minimum = (int) config('pembanding_scoring.candidate_pool.minimum', 300);
        $multiplier = (int) config('pembanding_scoring.candidate_pool.multiplier', 10);
        $maximum = (int) config('pembanding_scoring.candidate_pool.maximum', 1000);

        return min($maximum, max($minimum, $resultLimit * $multiplier));
    }
}
