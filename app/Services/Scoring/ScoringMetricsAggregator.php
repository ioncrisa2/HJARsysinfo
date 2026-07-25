<?php

namespace App\Services\Scoring;

use App\DTOs\Scoring\ScoringPipelineRun;
use App\Models\Pembanding;
use Illuminate\Support\Collection;

class ScoringMetricsAggregator
{
    public function __construct(
        private readonly ScoringQualityMetricsAggregator $quality,
    ) {}

    public function summarize(ScoringPipelineRun $run): array
    {
        $scored = $run->ranking->scoredCandidates;
        $results = $run->ranking->results;

        return array_merge([
            'pipeline' => $run->pipeline,
            'method_version' => $run->methodVersion,
            'requested_result_limit' => $run->requestedResultLimit,
            'radius_bucket' => $this->radiusBucket($run->radiusMeters),
            'pool_target' => $run->poolTarget,
            'pool_count' => $run->candidatePool->count(),
            'scored_count' => $scored->count(),
            'result_count' => $results->count(),
            'scoring_latency_ms' => [
                'retrieval' => $run->retrievalMilliseconds,
                'ranking' => $run->rankingMilliseconds,
                'total' => $run->totalMilliseconds(),
            ],
            'pool_retrieval_stage_counts' => $this->counts($run->candidatePool, 'retrieval_stage'),
            'result_retrieval_stage_counts' => $this->counts($results, 'retrieval_stage'),
            'pool_fallback' => $this->fallback($run->candidatePool),
            'result_fallback' => $this->fallback($results),
        ], $this->quality->summarize($scored));
    }

    private function counts(Collection $items, string $attribute): array
    {
        return $items->pluck($attribute)
            ->filter(fn ($value): bool => is_string($value) && $value !== '')
            ->countBy()
            ->sortKeys()
            ->all();
    }

    private function fallback(Collection $items): array
    {
        $count = $items->filter(
            fn (Pembanding $candidate): bool => (bool) ($candidate->is_fallback ?? false),
        )->count();

        return [
            'used' => $count > 0,
            'count' => $count,
            'rate' => $items->isEmpty() ? 0.0 : round(($count / $items->count()) * 100, 2),
        ];
    }

    private function radiusBucket(?int $radiusMeters): string
    {
        return match (true) {
            $radiusMeters === null => 'default',
            $radiusMeters <= 1000 => '0-1km',
            $radiusMeters <= 3000 => '1-3km',
            $radiusMeters <= 5000 => '3-5km',
            $radiusMeters <= 10000 => '5-10km',
            $radiusMeters <= 25000 => '10-25km',
            default => '25km+',
        };
    }
}
