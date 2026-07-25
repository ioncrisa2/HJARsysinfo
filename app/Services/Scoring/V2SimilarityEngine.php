<?php

namespace App\Services\Scoring;

use App\Contracts\Scoring\SimilarityEngine;
use App\DTOs\Scoring\SimilarityResult;
use App\Models\Pembanding;
use App\Services\Scoring\Components\ClassificationSimilarityService;
use App\Services\Scoring\Components\LegalSimilarityService;
use App\Services\Scoring\Components\LocationSimilarityService;
use App\Services\Scoring\Components\PhysicalSimilarityService;
use App\Services\Scoring\Components\SiteSimilarityService;

class V2SimilarityEngine implements SimilarityEngine
{
    public function __construct(
        private readonly LocationSimilarityService $location,
        private readonly ClassificationSimilarityService $classification,
        private readonly PhysicalSimilarityService $physical,
        private readonly LegalSimilarityService $legal,
        private readonly SiteSimilarityService $site,
        private readonly CoverageService $coverage,
        private readonly ReferenceProfileService $referenceProfile,
    ) {}

    public function evaluate(Pembanding $reference, Pembanding $candidate): SimilarityResult
    {
        $components = array_merge(
            $this->location->evaluate($reference, $candidate),
            $this->classification->evaluate($reference, $candidate),
            $this->physical->evaluate($reference, $candidate),
            $this->legal->evaluate($reference, $candidate),
            $this->site->evaluate($reference, $candidate),
        );
        $applicable = $this->referenceProfile->applicableComponents($reference);

        if ($applicable !== []) {
            $components = array_intersect_key($components, array_flip($applicable));
        }

        return $this->coverage->summarize($components);
    }
}
