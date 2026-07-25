<?php

namespace App\Services\Scoring;

use App\DTOs\Scoring\RankingOutcome;
use App\Models\Pembanding;
use Illuminate\Support\Collection;

class LegacySimilarityRankingService
{
    private const FALLBACK_SCORE_FACTOR = 0.75;

    public function __construct(private readonly PembandingScorer $scorer) {}

    public function rank(Pembanding $reference, Collection $candidates): Collection
    {
        return $this->rankWithOutcome($reference, $candidates)->results;
    }

    public function rankWithOutcome(
        Pembanding $reference,
        Collection $candidates,
    ): RankingOutcome {
        $legacyReference = clone $reference;
        foreach (['luas_tanah', 'luas_bangunan', 'lebar_jalan'] as $attribute) {
            if ($legacyReference->getAttribute($attribute) === null) {
                $legacyReference->setAttribute($attribute, 0);
            }
        }

        $scored = $candidates->map(function (Pembanding $candidate) use ($legacyReference) {
            $candidate->score = $this->scorer->score($legacyReference, $candidate);
            $candidate->similarity_score = $candidate->score;
            $candidate->scoring_status = 'legacy';
            $candidate->method_version = (string) config(
                'pembanding_scoring.methods.v1.version',
                'legacy-v1.0',
            );
            $candidate->priority_rank ??= 99;

            return $candidate;
        });

        $isGudang = $scored->contains(
            fn (Pembanding $candidate): bool => (int) $candidate->priority_rank !== 99,
        );
        $sorted = $isGudang
            ? $scored->sortBy([['priority_rank', 'asc'], ['score', 'desc']])->values()
            : $scored->sortByDesc('score')->values();

        $results = $sorted->map(function (Pembanding $candidate, int $index) {
            $candidate->rank = $index + 1;
            $candidate->similarity_rank = $index + 1;

            if ($candidate->is_fallback ?? false) {
                $candidate->score = round($candidate->score * self::FALLBACK_SCORE_FACTOR, 6);
                $candidate->similarity_score = $candidate->score;
                $candidate->fallback_reason = 'Tidak ada kandidat legacy dengan peruntukan yang diizinkan.';
            }

            return $candidate;
        });

        return new RankingOutcome($scored, $results);
    }
}
