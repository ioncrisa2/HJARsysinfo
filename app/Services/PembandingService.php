<?php

namespace App\Services;

use App\Enums\Peruntukan;
use App\Models\Pembanding;
use App\Repositories\PembandingRepository;
use App\Services\Scoring\PembandingScorer;
use App\Services\Peruntukan\PeruntukanGroupService;
use BackedEnum;
use Illuminate\Support\Collection;

class PembandingService
{
    protected const FALLBACK_SCORE_FACTOR = 0.75;

    public function __construct(
        protected PembandingRepository $repository,
        protected PembandingScorer $scorer,
        protected PeruntukanGroupService $groupService
    ) {}

    public function findSimilar(
        Pembanding $input,
        int $limit = 100,
        ?int $radiusMeters = null
    ): Collection
    {
        $inputPeruntukan = $this->resolvePeruntukanEnum($input);

        $results = $inputPeruntukan === Peruntukan::Gudang
            ? $this->findSimilarForGudang($input, $limit, $radiusMeters)
            : $this->findSimilarDefault($input, $limit, $inputPeruntukan, $radiusMeters);

        if ($results->isNotEmpty()) {
            return $results;
        }

        // Ruko harus tetap strict: tanpa fallback lintas peruntukan.
        if ($inputPeruntukan === Peruntukan::Ruko) {
            return $results;
        }

        return $this->findNearestFallback($input, $limit, $radiusMeters);
    }

    protected function findSimilarDefault(
        Pembanding $input,
        int $limit,
        ?Peruntukan $inputPeruntukan,
        ?int $radiusMeters = null
    ): Collection
    {
        $allowedPeruntukan = $this->groupService->getAllowedPeruntukan($inputPeruntukan);

        $candidates = $this->repository->getGeoCandidates(
            $input,
            $limit,
            $allowedPeruntukan,
            radiusMeters: $radiusMeters
        );

        return $this->scoreAndSort($input, $candidates);
    }

    protected function findSimilarForGudang(
        Pembanding $input,
        int $limit,
        ?int $radiusMeters = null
    ): Collection
    {
        $results = $this->fetchGudangCandidates($input, $limit, $radiusMeters);

        return $this->scoreAndSort($input, $results, multiSort: true);
    }

    protected function fetchGudangCandidates(
        Pembanding $input,
        int $limit,
        ?int $radiusMeters = null
    ): Collection
    {
        $results = collect();

        // Priority 1: Direct warehouse matches
        $results = $results->merge(
            $this->fetchPriorityCandidates($input, Peruntukan::Gudang, 1, $limit, $radiusMeters)
        );

        // Priority 2: Empty land with similar area
        if ($results->count() < $limit) {
            $results = $results->merge(
                $this->fetchEmptyLandCandidates($input, 2, $limit - $results->count(), $radiusMeters)
            );
        }

        // Priority 3: Mixed-use properties
        if ($results->count() < $limit) {
            $results = $results->merge(
                $this->fetchMixedUseCandidates($input, 3, $limit - $results->count(), $radiusMeters)
            );
        }

        return $results;
    }

    protected function fetchPriorityCandidates(
        Pembanding $input,
        Peruntukan $peruntukan,
        int $priority,
        int $limit,
        ?int $radiusMeters = null
    ): Collection {
        return $this->repository
            ->getGeoCandidates($input, $limit, [$peruntukan->value], radiusMeters: $radiusMeters)
            ->map(fn (Pembanding $item) => $this->setPriority($item, $priority));
    }

    protected function fetchEmptyLandCandidates(
        Pembanding $input,
        int $priority,
        int $limit,
        ?int $radiusMeters = null
    ): Collection {
        [$minArea, $maxArea] = $this->calculateAreaBounds($input);

        return $this->repository
            ->getGeoCandidates(
                $input,
                $limit,
                [Peruntukan::TanahKosong->value],
                districtId: $input->district_id,
                regencyId: $input->regency_id,
                minTotalArea: $minArea,
                maxTotalArea: $maxArea,
                radiusMeters: $radiusMeters,
            )
            ->map(fn (Pembanding $item) => $this->setPriority($item, $priority));
    }

    protected function fetchMixedUseCandidates(
        Pembanding $input,
        int $priority,
        int $limit,
        ?int $radiusMeters = null
    ): Collection {
        [$minArea, $maxArea] = $this->calculateAreaBounds($input);

        // Try with district first
        $candidates = $this->repository->getGeoCandidates(
            $input,
            $limit,
            [Peruntukan::Campuran->value],
            districtId: $input->district_id,
            regencyId: $input->regency_id,
            minTotalArea: $minArea,
            maxTotalArea: $maxArea,
            radiusMeters: $radiusMeters,
        );

        // Fallback to regency only if needed
        if ($candidates->isEmpty() && $input->regency_id) {
            $candidates = $this->repository->getGeoCandidates(
                $input,
                $limit,
                [Peruntukan::Campuran->value],
                districtId: null,
                regencyId: $input->regency_id,
                minTotalArea: $minArea,
                maxTotalArea: $maxArea,
                radiusMeters: $radiusMeters,
            );
        }

        return $candidates->map(fn (Pembanding $item) => $this->setPriority($item, $priority));
    }

    protected function calculateAreaBounds(Pembanding $input): array
    {
        $totalArea = ($input->luas_tanah ?? 0) + ($input->luas_bangunan ?? 0);

        if ($totalArea <= 0) {
            return [null, null];
        }

        return [$totalArea * 0.8, $totalArea * 1.25];
    }

    protected function scoreAndSort(
        Pembanding $input,
        Collection $candidates,
        bool $multiSort = false
    ): Collection {
        $scored = $candidates->map(function (Pembanding $item) use ($input) {
            $item->score = $this->scorer->score($input, $item);
            $item->priority_rank ??= 99;
            return $item;
        });

        if ($multiSort) {
            return $scored->sortBy([
                ['priority_rank', 'asc'],
                ['score', 'desc'],
            ])->values();
        }

        return $scored->sortByDesc('score')->values();
    }

    protected function setPriority(Pembanding $item, int $priority): Pembanding
    {
        $item->priority_rank = $priority;
        return $item;
    }

    protected function findNearestFallback(
        Pembanding $input,
        int $limit,
        ?int $radiusMeters = null
    ): Collection
    {
        // Bypass district/regency agar benar-benar mengambil kandidat terdekat secara geospasial.
        $candidates = $this->repository->getGeoCandidates(
            $input,
            $limit,
            [],
            districtId: '',
            regencyId: '',
            radiusMeters: $radiusMeters
        );

        return $this->scoreAndSort($input, $candidates)
            ->map(function (Pembanding $item) {
                $item->score = round($item->score * self::FALLBACK_SCORE_FACTOR, 6);
                $item->is_fallback = true;
                return $item;
            })
            ->values();
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
}   
