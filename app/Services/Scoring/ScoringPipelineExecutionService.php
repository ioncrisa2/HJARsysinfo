<?php

namespace App\Services\Scoring;

use App\DTOs\Scoring\ScoringPipelineRun;
use App\Models\Pembanding;

class ScoringPipelineExecutionService
{
    public function __construct(
        private readonly CandidateRetrievalService $retrieval,
        private readonly SimilarityRankingService $ranking,
        private readonly ScoringProfile $profile,
    ) {}

    public function run(
        Pembanding $reference,
        int $limit,
        ?int $radiusMeters,
    ): ScoringPipelineRun {
        $poolTarget = $this->retrieval->targetPoolSize($limit);
        [$pool, $retrievalMs] = $this->measure(
            fn () => $this->retrieval->retrieve($reference, $limit, $radiusMeters),
        );
        [$ranking, $rankingMs] = $this->measure(
            fn () => $this->ranking->rankWithOutcome($reference, $pool, $limit),
        );

        return new ScoringPipelineRun(
            pipeline: 'v2',
            methodVersion: $this->profile->version('v2'),
            candidatePool: $pool,
            ranking: $ranking,
            retrievalMilliseconds: $retrievalMs,
            rankingMilliseconds: $rankingMs,
            poolTarget: $poolTarget,
            requestedResultLimit: $limit,
            radiusMeters: $radiusMeters,
        );
    }

    public function runV2(
        Pembanding $reference,
        int $limit,
        ?int $radiusMeters,
    ): ScoringPipelineRun {
        return $this->run($reference, $limit, $radiusMeters);
    }

    private function measure(callable $operation): array
    {
        $startedAt = hrtime(true);
        $result = $operation();
        $milliseconds = (hrtime(true) - $startedAt) / 1_000_000;

        return [$result, round($milliseconds, 4)];
    }
}
