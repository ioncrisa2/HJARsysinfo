<?php

namespace App\DTOs\Scoring;

use App\Models\Pembanding;
use Illuminate\Support\Collection;

final readonly class RankingOutcome
{
    /**
     * @param  Collection<int, Pembanding>  $scoredCandidates
     * @param  Collection<int, Pembanding>  $results
     */
    public function __construct(
        public Collection $scoredCandidates,
        public Collection $results,
    ) {}
}
