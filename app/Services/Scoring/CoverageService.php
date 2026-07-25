<?php

namespace App\Services\Scoring;

use App\DTOs\Scoring\ComponentScore;
use App\DTOs\Scoring\SimilarityResult;

class CoverageService
{
    public function __construct(private readonly ScoringProfile $profile) {}

    /**
     * @param  array<string, ComponentScore>  $components
     */
    public function summarize(array $components): SimilarityResult
    {
        $totalWeight = array_sum(array_map(
            static fn (ComponentScore $component): float => $component->weight,
            $components,
        ));
        $applicableWeight = array_sum(array_map(
            static fn (ComponentScore $component): float => $component->applicable ? $component->weight : 0,
            $components,
        ));
        $availableWeight = array_sum(array_map(
            static fn (ComponentScore $component): float => $component->applicable
                && $component->candidateAvailable ? $component->weight : 0,
            $components,
        ));
        $weightedSum = array_sum(array_map(
            static fn (ComponentScore $component): float => $component->contribution,
            $components,
        ));

        $referenceCoverage = $totalWeight > 0 ? ($applicableWeight / $totalWeight) * 100 : 0;
        $scoreCoverage = $applicableWeight > 0 ? ($availableWeight / $applicableWeight) * 100 : 0;
        $score = $applicableWeight > 0 ? ($weightedSum / $applicableWeight) * 100 : 0;

        $status = $this->status($applicableWeight, $referenceCoverage, $scoreCoverage);
        $warnings = array_values(array_filter(array_map(
            static fn (ComponentScore $component): ?string => $component->warning,
            $components,
        )));

        if ($status === 'insufficient_input') {
            $warnings[] = sprintf(
                'Coverage acuan %.2f%% di bawah minimum; skor similarity tidak layak dijadikan dasar ranking.',
                $referenceCoverage,
            );
        } elseif ($status === 'partial_candidate_data') {
            $warnings[] = sprintf(
                'Coverage kandidat %.2f%% di bawah minimum; skor similarity bersifat parsial.',
                $scoreCoverage,
            );
        }

        return new SimilarityResult(
            score: round($score, 6),
            referenceCoverage: round($referenceCoverage, 2),
            scoreCoverage: round($scoreCoverage, 2),
            status: $status,
            methodVersion: $this->profile->version(),
            components: $components,
            warnings: array_values(array_unique($warnings)),
        );
    }

    private function status(float $applicable, float $referenceCoverage, float $scoreCoverage): string
    {
        if ($applicable <= 0 || $referenceCoverage < $this->profile->setting('minimum_reference_coverage', 60)) {
            return 'insufficient_input';
        }

        if ($scoreCoverage < $this->profile->setting('minimum_candidate_coverage', 60)) {
            return 'partial_candidate_data';
        }

        return 'scored';
    }
}
