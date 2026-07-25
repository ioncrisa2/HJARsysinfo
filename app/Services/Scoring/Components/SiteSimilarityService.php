<?php

namespace App\Services\Scoring\Components;

use App\DTOs\Scoring\ComponentScore;
use App\Models\Pembanding;
use App\Services\Scoring\ScoringAttributeResolver;
use App\Services\Scoring\ScoringProfile;

class SiteSimilarityService
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
        return [
            'posisi_tanah' => $this->categorical(
                'posisi_tanah',
                'position_similarity',
                $this->resolver->position($reference),
                $this->resolver->position($candidate),
                $this->profile->setting('default_position_similarity', 0.3),
            ),
            'kondisi_tanah' => $this->categorical(
                'kondisi_tanah',
                'condition_similarity',
                $this->resolver->condition($reference),
                $this->resolver->condition($candidate),
                $this->profile->setting('default_condition_similarity', 0.25),
            ),
        ];
    }

    private function categorical(
        string $name,
        string $matrix,
        ?string $reference,
        ?string $candidate,
        float $default,
    ): ComponentScore {
        $weight = $this->profile->weight($name);

        if (! $reference) {
            return ComponentScore::notApplicable($name, $weight);
        }

        if (! $candidate) {
            return ComponentScore::missingCandidate($name, $weight);
        }

        return ComponentScore::scored(
            $name,
            $weight,
            $this->profile->similarity($matrix, $reference, $candidate, $default),
            ['reference' => $reference, 'candidate' => $candidate, 'exact' => $reference === $candidate],
        );
    }
}
