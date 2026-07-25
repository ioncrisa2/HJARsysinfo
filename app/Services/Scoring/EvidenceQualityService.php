<?php

namespace App\Services\Scoring;

use App\DTOs\Scoring\EvidenceQualityResult;
use App\Models\Pembanding;
use Carbon\CarbonImmutable;

class EvidenceQualityService
{
    public function __construct(private readonly ScoringAttributeResolver $resolver) {}

    public function evaluate(Pembanding $reference, Pembanding $candidate): EvidenceQualityResult
    {
        $type = $this->resolver->evidenceType($candidate);
        $typeScore = match ($type) {
            'transaction' => 100,
            'offer' => 70,
            default => 40,
        };
        $tier = match ($type) {
            'transaction' => 'primary',
            'offer' => 'secondary',
            default => 'unknown',
        };

        $referenceDate = $reference->getAttribute('reference_date')
            ?? $reference->getAttribute('tanggal_data')
            ?? CarbonImmutable::today();
        $candidateDate = $candidate->getAttribute('tanggal_data');
        $warnings = [];

        if (! $candidateDate) {
            $recencyScore = 0;
            $warnings[] = 'Tanggal data kandidat tidak tersedia.';
        } else {
            $referenceMoment = CarbonImmutable::parse($referenceDate);
            $candidateMoment = CarbonImmutable::parse($candidateDate);

            if ($candidateMoment->isAfter($referenceMoment)) {
                return new EvidenceQualityResult(
                    score: 0,
                    tier: 'reviewer_only',
                    components: [
                        'evidence_type' => $type,
                        'type_score' => $typeScore,
                        'recency_score' => 0,
                    ],
                    warnings: ['Tanggal data kandidat berada setelah tanggal acuan penilaian.'],
                );
            }

            $days = $referenceMoment->diffInDays($candidateMoment);
            $recencyScore = exp(-$days / 730) * 100;
        }

        return new EvidenceQualityResult(
            score: ($typeScore * 0.6) + ($recencyScore * 0.4),
            tier: $tier,
            components: [
                'evidence_type' => $type,
                'type_score' => $typeScore,
                'recency_score' => round($recencyScore, 2),
            ],
            warnings: $warnings,
        );
    }
}
