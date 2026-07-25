<?php

namespace App\Services\Scoring\Components;

use App\DTOs\Scoring\ComponentScore;
use App\Models\Pembanding;
use App\Services\Geo\GeoDistanceCalculator;
use App\Services\Scoring\ScoringProfile;

class LocationSimilarityService
{
    public function __construct(
        private readonly GeoDistanceCalculator $distanceCalculator,
        private readonly ScoringProfile $profile,
    ) {}

    /**
     * @return array<string, ComponentScore>
     */
    public function evaluate(Pembanding $reference, Pembanding $candidate): array
    {
        $weight = $this->profile->weight('distance');
        $referenceLat = $reference->getAttribute('latitude');
        $referenceLng = $reference->getAttribute('longitude');

        if ($referenceLat === null || $referenceLng === null) {
            return ['distance' => ComponentScore::notApplicable('distance', $weight)];
        }

        $candidateLat = $candidate->getAttribute('latitude');
        $candidateLng = $candidate->getAttribute('longitude');

        if ($candidateLat === null || $candidateLng === null) {
            return ['distance' => ComponentScore::missingCandidate('distance', $weight)];
        }

        $distance = is_numeric($candidate->getAttribute('distance'))
            ? (float) $candidate->getAttribute('distance')
            : $this->distanceCalculator->calculate(
                (float) $referenceLat,
                (float) $referenceLng,
                (float) $candidateLat,
                (float) $candidateLng,
            );

        $decay = max(1, $this->profile->setting('distance_decay_meters', 2500));

        return [
            'distance' => ComponentScore::scored(
                'distance',
                $weight,
                exp(-$distance / $decay),
                ['distance_meters' => round($distance, 2)],
            ),
        ];
    }
}
