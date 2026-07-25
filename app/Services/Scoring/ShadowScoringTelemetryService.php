<?php

namespace App\Services\Scoring;

use App\DTOs\Scoring\ScoringPipelineRun;
use App\Models\Pembanding;
use Illuminate\Container\Attributes\Log;
use Psr\Log\LoggerInterface;
use Throwable;

class ShadowScoringTelemetryService
{
    public function __construct(
        #[Log('scoring')] private readonly LoggerInterface $logger,
        private readonly ScoringTelemetrySampler $sampler,
    ) {}

    public function record(
        Pembanding $reference,
        ScoringPipelineRun $legacy,
        ScoringPipelineRun $v2,
        string $comparisonId,
    ): void {
        try {
            $this->recordComparison($reference, $legacy, $v2, $comparisonId);
        } catch (Throwable) {
            // Shadow telemetry is best-effort.
        }
    }

    private function recordComparison(
        Pembanding $reference,
        ScoringPipelineRun $legacy,
        ScoringPipelineRun $v2,
        string $comparisonId,
    ): void {
        if (config('pembanding_scoring.mode') !== 'v2_shadow' || ! $this->sampler->shadowLog()) {
            return;
        }

        $legacyResults = $legacy->ranking->results;
        $v2Results = $v2->ranking->results;
        $legacyById = $legacyResults->keyBy(fn (Pembanding $item) => (string) $item->getKey());
        $v2ById = $v2Results->keyBy(fn (Pembanding $item) => (string) $item->getKey());
        $sharedIds = $legacyById->keys()->intersect($v2ById->keys());

        $deltas = $sharedIds->map(
            fn (string $id): float => abs(
                (float) $legacyById->get($id)->score - (float) $v2ById->get($id)->score,
            ),
        );

        $rankDeltas = $sharedIds->map(fn (string $id): int => abs(
            (int) $legacyById->get($id)->rank - (int) $v2ById->get($id)->rank,
        ));
        $denominator = min(10, max($legacyResults->count(), $v2Results->count()));
        $topOverlap = $legacyResults->take(10)->pluck('id')
            ->intersect($v2Results->take(10)->pluck('id'))->count();

        $this->logger->info('pembanding_scoring_shadow', [
            'event_schema_version' => 2,
            'comparison_id' => $comparisonId,
            'reference_source' => $reference->exists ? 'stored' : 'payload',
            'legacy_method_version' => $legacy->methodVersion,
            'v2_method_version' => $v2->methodVersion,
            'legacy_pool_count' => $legacy->candidatePool->count(),
            'v2_pool_count' => $v2->candidatePool->count(),
            'legacy_result_count' => $legacyResults->count(),
            'v2_result_count' => $v2Results->count(),
            'shared_result_count' => $sharedIds->count(),
            'legacy_latency_ms' => [
                'retrieval' => $legacy->retrievalMilliseconds,
                'ranking' => $legacy->rankingMilliseconds,
                'total' => $legacy->totalMilliseconds(),
            ],
            'v2_latency_ms' => [
                'retrieval' => $v2->retrievalMilliseconds,
                'ranking' => $v2->rankingMilliseconds,
                'total' => $v2->totalMilliseconds(),
            ],
            'top_candidate_changed' => $legacyResults->first()?->getKey()
                !== $v2Results->first()?->getKey(),
            'top_10_overlap_rate' => $denominator === 0
                ? 0.0
                : round(($topOverlap / $denominator) * 100, 2),
            'average_absolute_delta' => $deltas->isEmpty()
                ? null
                : round((float) $deltas->average(), 4),
            'maximum_absolute_delta' => $deltas->isEmpty()
                ? null
                : round((float) $deltas->max(), 4),
            'average_absolute_rank_delta' => $rankDeltas->isEmpty()
                ? null
                : round((float) $rankDeltas->average(), 2),
        ]);
    }
}
