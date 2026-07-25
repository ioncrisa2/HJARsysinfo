<?php

namespace App\Services\Scoring\Components;

use App\DTOs\Scoring\ComponentScore;
use App\Models\Pembanding;
use App\Services\Peruntukan\PeruntukanGroupService;
use App\Services\Scoring\ScoringAttributeResolver;
use App\Services\Scoring\ScoringProfile;

class ClassificationSimilarityService
{
    public function __construct(
        private readonly ScoringAttributeResolver $resolver,
        private readonly ScoringProfile $profile,
        private readonly PeruntukanGroupService $groupService,
    ) {}

    /**
     * @return array<string, ComponentScore>
     */
    public function evaluate(Pembanding $reference, Pembanding $candidate): array
    {
        return [
            'peruntukan' => $this->peruntukan($reference, $candidate),
            'jenis_objek' => $this->objectType($reference, $candidate),
        ];
    }

    private function peruntukan(Pembanding $reference, Pembanding $candidate): ComponentScore
    {
        $weight = $this->profile->weight('peruntukan');
        $a = $this->resolver->peruntukan($reference);
        $b = $this->resolver->peruntukan($candidate);

        if (! $a) {
            return ComponentScore::notApplicable('peruntukan', $weight);
        }

        if (! $b) {
            return ComponentScore::missingCandidate('peruntukan', $weight);
        }

        $similarity = $this->profile->similarity('peruntukan_similarity', $a, $b, -1);

        if ($similarity < 0) {
            $sameGroup = $this->groupService->getGroup($a) !== null
                && $this->groupService->getGroup($a) === $this->groupService->getGroup($b);
            $similarity = $sameGroup
                ? $this->profile->setting('same_peruntukan_group_similarity', 0.5)
                : 0;
        }

        return ComponentScore::scored(
            'peruntukan',
            $weight,
            $similarity,
            ['reference' => $a, 'candidate' => $b, 'exact' => $a === $b],
        );
    }

    private function objectType(Pembanding $reference, Pembanding $candidate): ComponentScore
    {
        $weight = $this->profile->weight('jenis_objek');
        $a = $this->resolver->objectType($reference);
        $b = $this->resolver->objectType($candidate);

        if (! $a) {
            return ComponentScore::notApplicable('jenis_objek', $weight);
        }

        if (! $b) {
            return ComponentScore::missingCandidate('jenis_objek', $weight);
        }

        return ComponentScore::scored(
            'jenis_objek',
            $weight,
            $this->profile->similarity(
                'object_similarity',
                $a,
                $b,
                $this->profile->setting('default_object_similarity'),
            ),
            ['reference' => $a, 'candidate' => $b, 'exact' => $a === $b],
        );
    }
}
