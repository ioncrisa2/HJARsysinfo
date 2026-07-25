<?php

namespace App\Services\Scoring\Components;

use App\DTOs\Scoring\ComponentScore;
use App\Models\Pembanding;
use App\Services\Scoring\ScoringAttributeResolver;
use App\Services\Scoring\ScoringProfile;

class LegalSimilarityService
{
    public function __construct(
        private readonly ScoringAttributeResolver $resolver,
        private readonly ScoringProfile $profile,
    ) {}

    /**
     * @return array<string, ComponentScore>
     */
    public function evaluate(Pembanding $reference, Pembanding $candidate): array
    {
        $weight = $this->profile->weight('dokumen_tanah');
        $a = $this->resolver->legal($reference);
        $b = $this->resolver->legal($candidate);

        if (! $a) {
            return ['dokumen_tanah' => ComponentScore::notApplicable('dokumen_tanah', $weight)];
        }

        if (! $b) {
            return ['dokumen_tanah' => ComponentScore::missingCandidate('dokumen_tanah', $weight)];
        }

        return [
            'dokumen_tanah' => ComponentScore::scored(
                'dokumen_tanah',
                $weight,
                $this->profile->similarity(
                    'legal_similarity',
                    $a,
                    $b,
                    $this->profile->setting('default_legal_similarity', 0.25),
                ),
                ['reference' => $a, 'candidate' => $b, 'exact' => $a === $b],
            ),
        ];
    }
}
