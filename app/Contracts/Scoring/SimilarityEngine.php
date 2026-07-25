<?php

namespace App\Contracts\Scoring;

use App\DTOs\Scoring\SimilarityResult;
use App\Models\Pembanding;

interface SimilarityEngine
{
    public function evaluate(Pembanding $reference, Pembanding $candidate): SimilarityResult;
}
