<?php

namespace App\Services\Scoring;

use App\DTOs\Scoring\ScoringPipelineRun;
use App\Models\Pembanding;

class ScoringPipelineExecutionService
{
    public function __construct(
        private readonly LegacyCandidateRetrievalService $legacyRetrieval,
        private readonly LegacySimilarityRankingService $legacyRanking,
        private readonly CandidateRetrievalService $v2Retrieval,
        private readonly SimilarityRankingService $v2Ranking,
        private readonly ScoringProfile $profile,
    ) {}

    public function runLegacy(
        Pembanding $reference,
        int $limit,
        ?int $radiusMeters,
    ): ScoringPipelineRun {
        [$pool, $retrievalMs] = $this->measure(
            fn () => $this->legacyRetrieval->retrieve($reference, $limit, $radiusMeters),
        );
        [$ranking, $rankingMs] = $this->measure(
            fn () => $this->legacyRanking->rankWithOutcome($reference, $pool),
        );

        return new ScoringPipelineRun(
            pipeline: 'legacy',
            methodVersion: $this->profile->version('v1'),
            candidatePool: $pool,
            ranking: $ranking,
            retrievalMilliseconds: $retrievalMs,
            rankingMilliseconds: $rankingMs,
            poolTarget: $limit,
            requestedResultLimit: $limit,
            radiusMeters: $radiusMeters,
        );
    }

    public function runV2(
        Pembanding $reference,
        int $limit,
        ?int $radiusMeters,
    ): ScoringPipelineRun {
        $poolTarget = $this->v2Retrieval->targetPoolSize($limit);
        [$pool, $retrievalMs] = $this->measure(
            fn () => $this->v2Retrieval->retrieve($reference, $limit, $radiusMeters),
        );
        [$ranking, $rankingMs] = $this->measure(
            fn () => $this->v2Ranking->rankWithOutcome($reference, $pool, $limit),
        );

        return new ScoringPipelineRun(
            pipeline: 'v2',
            methodVersion: $this->profile->version(),
            candidatePool: $pool,
            ranking: $ranking,
            retrievalMilliseconds: $retrievalMs,
            rankingMilliseconds: $rankingMs,
            poolTarget: $poolTarget,
            requestedResultLimit: $limit,
            radiusMeters: $radiusMeters,
        );
    }

    private function measure(callable $operation): array
    {
        $startedAt = hrtime(true);
        $result = $operation();
        $milliseconds = (hrtime(true) - $startedAt) / 1_000_000;

        return [$result, round($milliseconds, 4)];
    }
}
