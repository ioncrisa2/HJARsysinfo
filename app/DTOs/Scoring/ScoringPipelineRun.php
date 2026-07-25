<?php

namespace App\DTOs\Scoring;

use App\Models\Pembanding;
use Illuminate\Support\Collection;

final readonly class ScoringPipelineRun
{
    /**
     * @param  Collection<int, Pembanding>  $candidatePool
     */
    public function __construct(
        public string $pipeline,
        public string $methodVersion,
        public Collection $candidatePool,
        public RankingOutcome $ranking,
        public float $retrievalMilliseconds,
        public float $rankingMilliseconds,
        public int $poolTarget,
        public int $requestedResultLimit,
        public ?int $radiusMeters,
    ) {}

    public function totalMilliseconds(): float
    {
        return round($this->retrievalMilliseconds + $this->rankingMilliseconds, 4);
    }
}
