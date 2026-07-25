<?php

namespace App\Services\Scoring\Components;

use App\DTOs\Scoring\ComponentScore;
use App\Models\Pembanding;
use App\Services\Scoring\ScoringProfile;

class PhysicalSimilarityService
{
    public function __construct(private readonly ScoringProfile $profile) {}

    /**
     * @return array<string, ComponentScore>
     */
    public function evaluate(Pembanding $reference, Pembanding $candidate): array
    {
        return [
            'luas_tanah' => $this->ratio($reference, $candidate, 'luas_tanah'),
            'luas_bangunan' => $this->ratio($reference, $candidate, 'luas_bangunan'),
            'lebar_jalan' => $this->roadWidth($reference, $candidate),
        ];
    }

    private function ratio(
        Pembanding $reference,
        Pembanding $candidate,
        string $attribute,
    ): ComponentScore {
        $weight = $this->profile->weight($attribute);
        $a = $reference->getAttribute($attribute);
        $b = $candidate->getAttribute($attribute);

        if ($a === null) {
            return ComponentScore::notApplicable($attribute, $weight);
        }

        if ($b === null) {
            return ComponentScore::missingCandidate($attribute, $weight);
        }

        $a = max(0, (float) $a);
        $b = max(0, (float) $b);
        $similarity = $a <= 0 || $b <= 0
            ? ($a == $b ? 1.0 : 0.0)
            : min($a, $b) / max($a, $b);

        return ComponentScore::scored(
            $attribute,
            $weight,
            $similarity,
            ['reference' => $a, 'candidate' => $b],
        );
    }

    private function roadWidth(Pembanding $reference, Pembanding $candidate): ComponentScore
    {
        $weight = $this->profile->weight('lebar_jalan');
        $a = $reference->getAttribute('lebar_jalan');
        $b = $candidate->getAttribute('lebar_jalan');

        if ($a === null) {
            return ComponentScore::notApplicable('lebar_jalan', $weight);
        }

        if ($b === null) {
            return ComponentScore::missingCandidate('lebar_jalan', $weight);
        }

        $difference = abs((float) $a - (float) $b);
        $decay = max(0.01, $this->profile->setting('road_width_decay_meters', 2));

        return ComponentScore::scored(
            'lebar_jalan',
            $weight,
            exp(-$difference / $decay),
            ['reference' => (float) $a, 'candidate' => (float) $b, 'difference' => $difference],
        );
    }
}
