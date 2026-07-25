<?php

namespace App\Services\Scoring;

use App\Models\Pembanding;
use App\Repositories\PembandingRepository;
use App\Services\Peruntukan\PeruntukanGroupService;
use Illuminate\Support\Collection;

class LegacyCandidateRetrievalService
{
    public function __construct(
        private readonly PembandingRepository $repository,
        private readonly ScoringAttributeResolver $resolver,
        private readonly PeruntukanGroupService $groups,
    ) {}

    public function retrieve(
        Pembanding $input,
        int $limit,
        ?int $radiusMeters,
    ): Collection {
        $peruntukan = $this->resolver->peruntukan($input);
        $candidates = $peruntukan === 'gudang'
            ? $this->retrieveGudang($input, $limit, $radiusMeters)
            : $this->retrieveDefault($input, $limit, $peruntukan, $radiusMeters);

        if ($candidates->isNotEmpty() || $peruntukan === 'ruko') {
            return $candidates;
        }

        return $this->repository->getGeoCandidates(
            $input,
            $limit,
            [],
            districtId: '',
            regencyId: '',
            radiusMeters: $radiusMeters,
        )->map(function (Pembanding $candidate) {
            $candidate->is_fallback = true;
            $candidate->retrieval_stage = 'cross_peruntukan_fallback';

            return $candidate;
        });
    }

    private function retrieveDefault(
        Pembanding $input,
        int $limit,
        ?string $peruntukan,
        ?int $radiusMeters,
    ): Collection {
        return $this->repository->getGeoCandidates(
            $input,
            $limit,
            $this->groups->getAllowedPeruntukan($peruntukan),
            radiusMeters: $radiusMeters,
        )->each(function (Pembanding $candidate) {
            $candidate->priority_rank = 99;
            $candidate->retrieval_stage = 'district';
        });
    }

    private function retrieveGudang(
        Pembanding $input,
        int $limit,
        ?int $radiusMeters,
    ): Collection {
        $results = $this->priorityCandidates($input, 'gudang', 1, $limit, $radiusMeters);

        if ($results->count() < $limit) {
            $results = $results->merge($this->areaCandidates(
                $input,
                'tanah_kosong',
                2,
                $limit - $results->count(),
                $radiusMeters,
            ));
        }

        if ($results->count() < $limit) {
            $remaining = $limit - $results->count();
            $mixed = $this->areaCandidates($input, 'campuran', 3, $remaining, $radiusMeters);

            if ($mixed->isEmpty() && $input->regency_id) {
                [$min, $max] = $this->areaBounds($input);
                $mixed = $this->repository->getGeoCandidates(
                    $input,
                    $remaining,
                    ['campuran'],
                    districtId: null,
                    regencyId: $input->regency_id,
                    minTotalArea: $min,
                    maxTotalArea: $max,
                    radiusMeters: $radiusMeters,
                )->map(fn (Pembanding $candidate) => $this->setPriority($candidate, 3));
            }

            $results = $results->merge($mixed);
        }

        return $results;
    }

    private function priorityCandidates(
        Pembanding $input,
        string $peruntukan,
        int $priority,
        int $limit,
        ?int $radiusMeters,
    ): Collection {
        return $this->repository
            ->getGeoCandidates($input, $limit, [$peruntukan], radiusMeters: $radiusMeters)
            ->map(fn (Pembanding $candidate) => $this->setPriority($candidate, $priority));
    }

    private function areaCandidates(
        Pembanding $input,
        string $peruntukan,
        int $priority,
        int $limit,
        ?int $radiusMeters,
    ): Collection {
        [$min, $max] = $this->areaBounds($input);

        return $this->repository->getGeoCandidates(
            $input,
            $limit,
            [$peruntukan],
            districtId: $input->district_id,
            regencyId: $input->regency_id,
            minTotalArea: $min,
            maxTotalArea: $max,
            radiusMeters: $radiusMeters,
        )->map(fn (Pembanding $candidate) => $this->setPriority($candidate, $priority));
    }

    private function areaBounds(Pembanding $input): array
    {
        $area = (float) ($input->luas_tanah ?? 0);

        if ($area <= 0) {
            $area = (float) ($input->luas_bangunan ?? 0);
        }

        return $area > 0 ? [$area * 0.8, $area * 1.25] : [null, null];
    }

    private function setPriority(Pembanding $candidate, int $priority): Pembanding
    {
        $candidate->priority_rank = $priority;
        $candidate->retrieval_stage = 'district';

        return $candidate;
    }
}
