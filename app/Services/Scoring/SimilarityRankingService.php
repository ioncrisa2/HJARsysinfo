<?php

namespace App\Services\Scoring;

use App\DTOs\Scoring\RankingOutcome;
use App\Models\Pembanding;
use Illuminate\Support\Collection;

class SimilarityRankingService
{
    public function __construct(
        private readonly V2SimilarityEngine $engine,
        private readonly EligibilityService $eligibility,
        private readonly EvidenceQualityService $evidence,
        private readonly ReportReadinessService $reportReadiness,
    ) {}

    public function rank(Pembanding $reference, Collection $candidates, int $limit): Collection
    {
        return $this->rankWithOutcome($reference, $candidates, $limit)->results;
    }

    public function rankWithOutcome(
        Pembanding $reference,
        Collection $candidates,
        int $limit,
    ): RankingOutcome {
        $scored = $candidates->map(function (Pembanding $candidate) use ($reference) {
            $eligibility = $this->eligibility->evaluate($reference, $candidate);

            if (! $eligibility->eligible) {
                return null;
            }

            $result = $this->engine->evaluate($reference, $candidate);
            $evidence = $this->evidence->evaluate($reference, $candidate);
            $report = $this->reportReadiness->evaluate($reference, $candidate);

            $candidate->score = $result->score;
            $candidate->similarity_score = $result->score;
            $candidate->reference_coverage = $result->referenceCoverage;
            $candidate->score_coverage = $result->scoreCoverage;
            $candidate->scoring_status = $result->status;
            $candidate->scoring_status_rank = $this->statusRank($result->status);
            $candidate->rankable = $result->status !== 'insufficient_input';
            $candidate->method_version = $result->methodVersion;
            $candidate->component_scores = $result->componentPayload();
            $candidate->eligibility_tier = $eligibility->tier;
            $candidate->eligibility_tier_rank = $eligibility->tierRank();
            $candidate->eligibility_reasons = $eligibility->reasons;
            $candidate->evidence_quality = $evidence->toArray();
            $candidate->evidence_quality_score = $evidence->score;
            $candidate->evidence_tier = $evidence->tier;
            $candidate->evidence_tier_rank = $this->evidenceTierRank($evidence->tier);
            $candidate->report_readiness = $report->status;
            $candidate->report_completeness = $report->completeness;
            $candidate->report_missing_fields = $report->missingFields;
            $candidate->record_version = $report->recordVersion;
            $candidate->warnings = array_values(array_unique(array_merge(
                $result->warnings,
                $eligibility->warnings,
                $evidence->warnings,
            )));
            $candidate->fallback_reason = $candidate->is_fallback
                ? 'Tidak ada kandidat dengan peruntukan yang diizinkan dalam radius pencarian.'
                : null;

            return $candidate;
        })->filter()->values();

        $similarityOrder = $scored->sort($this->similarityComparator(...))->values();
        $similarityOrder->each(
            fn (Pembanding $candidate, int $index) => $candidate->similarity_rank = $index + 1,
        );

        $results = $scored
            ->sort($this->finalComparator(...))
            ->take($limit)
            ->values()
            ->each(fn (Pembanding $candidate, int $index) => $candidate->rank = $index + 1);

        return new RankingOutcome($scored, $results);
    }

    private function similarityComparator(Pembanding $a, Pembanding $b): int
    {
        return $this->compare($a, $b, [
            ['scoring_status_rank', 'asc'],
            ['score', 'desc'],
            ['score_coverage', 'desc'],
            ['distance', 'asc'],
            ['id', 'asc'],
        ]);
    }

    private function finalComparator(Pembanding $a, Pembanding $b): int
    {
        return $this->compare($a, $b, [
            ['eligibility_tier_rank', 'asc'],
            ['evidence_tier_rank', 'asc'],
            ['scoring_status_rank', 'asc'],
            ['priority_rank', 'asc'],
            ['score', 'desc'],
            ['evidence_quality_score', 'desc'],
            ['score_coverage', 'desc'],
            ['distance', 'asc'],
            ['id', 'asc'],
        ]);
    }

    private function statusRank(string $status): int
    {
        return match ($status) {
            'scored' => 1,
            'partial_candidate_data' => 2,
            default => 3,
        };
    }

    private function evidenceTierRank(string $tier): int
    {
        return match ($tier) {
            'primary' => 1,
            'secondary' => 2,
            default => 3,
        };
    }

    private function compare(Pembanding $a, Pembanding $b, array $rules): int
    {
        foreach ($rules as [$attribute, $direction]) {
            $comparison = ($a->getAttribute($attribute) ?? 0) <=> ($b->getAttribute($attribute) ?? 0);

            if ($comparison !== 0) {
                return $direction === 'desc' ? -$comparison : $comparison;
            }
        }

        return 0;
    }
}
