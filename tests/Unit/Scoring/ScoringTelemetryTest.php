<?php

use App\DTOs\Scoring\RankingOutcome;
use App\DTOs\Scoring\ScoringPipelineRun;
use App\Models\Pembanding;
use App\Services\Scoring\ScoringAttributeResolver;
use App\Services\Scoring\ScoringMetricsAggregator;
use App\Services\Scoring\ScoringOperationalTelemetryService;
use App\Services\Scoring\ScoringTelemetrySampler;
use App\Services\Scoring\ShadowScoringTelemetryService;
use Illuminate\Support\Collection;
use Psr\Log\AbstractLogger;
use Tests\TestCase;

uses(TestCase::class);

function telemetryCandidate(int $id, array $attributes = []): Pembanding
{
    $candidate = new Pembanding;
    $candidate->setAttribute('id', $id);

    foreach ($attributes as $key => $value) {
        $candidate->setAttribute($key, $value);
    }

    return $candidate;
}

function telemetryRun(
    Collection $pool,
    Collection $scored,
    Collection $results,
    string $pipeline = 'v2',
    string $methodVersion = 'heuristic-v2.0',
    float $retrievalMilliseconds = 1.25,
    float $rankingMilliseconds = 2.5,
): ScoringPipelineRun {
    return new ScoringPipelineRun(
        pipeline: $pipeline,
        methodVersion: $methodVersion,
        candidatePool: $pool,
        ranking: new RankingOutcome($scored, $results),
        retrievalMilliseconds: $retrievalMilliseconds,
        rankingMilliseconds: $rankingMilliseconds,
        poolTarget: 300,
        requestedResultLimit: 1,
        radiusMeters: 10_000,
    );
}

it('aggregates pool metrics separately from final results', function () {
    $first = telemetryCandidate(1, [
        'retrieval_stage' => 'district',
        'reference_coverage' => 80,
        'score_coverage' => 50,
        'scoring_status' => 'partial_candidate_data',
        'eligibility_tier' => 'primary',
        'evidence_tier' => 'primary',
        'report_readiness' => 'incomplete',
        'report_missing_fields' => ['harga'],
        'component_scores' => [
            'lebar_jalan' => ['applicable' => true, 'candidate_available' => false],
        ],
    ]);
    $second = telemetryCandidate(2, [
        'retrieval_stage' => 'radius',
        'is_fallback' => true,
        'reference_coverage' => 80,
        'score_coverage' => 100,
        'scoring_status' => 'scored',
        'eligibility_tier' => 'fallback',
        'evidence_tier' => 'secondary',
        'report_readiness' => 'ready',
        'report_missing_fields' => [],
        'component_scores' => [],
    ]);
    $unscored = telemetryCandidate(3, ['retrieval_stage' => 'regency']);
    $metrics = app(ScoringMetricsAggregator::class)->summarize(
        telemetryRun(collect([$first, $second, $unscored]), collect([$first, $second]), collect([$first])),
    );

    expect($metrics)
        ->pool_count->toBe(3)
        ->scored_count->toBe(2)
        ->result_count->toBe(1)
        ->score_coverage->average->toBe(75.0)
        ->pool_fallback->count->toBe(1)
        ->result_fallback->count->toBe(0)
        ->missing_component_counts->lebar_jalan->toBe(1)
        ->report_missing_field_counts->harga->toBe(1);
});

it('keeps telemetry best effort and excludes sensitive context', function () {
    config()->set('pembanding_scoring.telemetry.enabled', true);
    config()->set('pembanding_scoring.telemetry.sample_rate', 1);

    $logger = new class extends AbstractLogger
    {
        public array $records = [];

        public function log($level, Stringable|string $message, array $context = []): void
        {
            $this->records[] = compact('level', 'message', 'context');
        }
    };
    $service = new ScoringOperationalTelemetryService(
        $logger,
        app(ScoringMetricsAggregator::class),
        app(ScoringAttributeResolver::class),
        app(ScoringTelemetrySampler::class),
    );
    $reference = telemetryCandidate(10, [
        'alamat_data' => 'Rahasia',
        'latitude' => -6.2,
        'longitude' => 106.8,
        'harga' => 1000000,
        'market_basis' => 'sale',
    ]);
    $run = telemetryRun(collect(), collect(), collect());

    $service->record('v2', $reference, $run, true);

    expect($logger->records)->toHaveCount(1);
    $serialized = json_encode($logger->records[0]['context'], JSON_THROW_ON_ERROR);
    expect($serialized)
        ->not->toContain('Rahasia')
        ->not->toContain('latitude')
        ->not->toContain('longitude')
        ->not->toContain('harga');

    config()->set('pembanding_scoring.telemetry.enabled', false);
    $service->record('v2', $reference, $run, true);
    expect($logger->records)->toHaveCount(1);

    config()->set('pembanding_scoring.telemetry.enabled', true);
    $throwingLogger = new class extends AbstractLogger
    {
        public function log($level, Stringable|string $message, array $context = []): void
        {
            throw new RuntimeException('logger unavailable');
        }
    };
    $bestEffort = new ScoringOperationalTelemetryService(
        $throwingLogger,
        app(ScoringMetricsAggregator::class),
        app(ScoringAttributeResolver::class),
        app(ScoringTelemetrySampler::class),
    );

    expect(fn () => $bestEffort->record('v2', $reference, $run, true))->not->toThrow(Throwable::class);

    $brokenMetrics = Mockery::mock(ScoringMetricsAggregator::class);
    $brokenMetrics->shouldReceive('summarize')->andThrow(new RuntimeException('metrics unavailable'));
    $safeAggregation = new ScoringOperationalTelemetryService(
        $logger,
        $brokenMetrics,
        app(ScoringAttributeResolver::class),
        app(ScoringTelemetrySampler::class),
    );

    expect(fn () => $safeAggregation->record('v2', $reference, $run, true))
        ->not->toThrow(Throwable::class);
});

it('correlates shadow runs and records paired comparison metrics', function () {
    config()->set('pembanding_scoring.mode', 'v2_shadow');
    config()->set('pembanding_scoring.shadow_log_enabled', true);

    $logger = new class extends AbstractLogger
    {
        public array $records = [];

        public function log($level, Stringable|string $message, array $context = []): void
        {
            $this->records[] = compact('level', 'message', 'context');
        }
    };
    $legacyFirst = telemetryCandidate(1, ['score' => 80, 'rank' => 1]);
    $shared = telemetryCandidate(2, ['score' => 70, 'rank' => 2]);
    $v2Shared = telemetryCandidate(2, ['score' => 75, 'rank' => 1]);
    $v2Second = telemetryCandidate(3, ['score' => 65, 'rank' => 2]);
    $legacy = telemetryRun(
        collect([$legacyFirst, $shared]),
        collect([$legacyFirst, $shared]),
        collect([$legacyFirst, $shared]),
        'legacy',
        'legacy-v1.0',
        3,
        4,
    );
    $v2 = telemetryRun(
        collect([$v2Shared, $v2Second]),
        collect([$v2Shared, $v2Second]),
        collect([$v2Shared, $v2Second]),
        'v2',
        'heuristic-v2.0',
        5,
        6,
    );
    $service = new ShadowScoringTelemetryService(
        $logger,
        app(ScoringTelemetrySampler::class),
    );

    $service->record(telemetryCandidate(10), $legacy, $v2, 'comparison-123');

    expect($logger->records)->toHaveCount(1);
    $context = $logger->records[0]['context'];
    expect($context)
        ->comparison_id->toBe('comparison-123')
        ->legacy_method_version->toBe('legacy-v1.0')
        ->v2_method_version->toBe('heuristic-v2.0')
        ->shared_result_count->toBe(1)
        ->top_candidate_changed->toBeTrue()
        ->average_absolute_delta->toBe(5.0)
        ->average_absolute_rank_delta->toBe(1.0)
        ->and($context['legacy_latency_ms']['total'])->toBe(7.0)
        ->and($context['v2_latency_ms']['total'])->toBe(11.0);
});

it('keeps shadow telemetry best effort when its logger fails', function () {
    config()->set('pembanding_scoring.mode', 'v2_shadow');
    config()->set('pembanding_scoring.shadow_log_enabled', true);

    $logger = new class extends AbstractLogger
    {
        public function log($level, Stringable|string $message, array $context = []): void
        {
            throw new RuntimeException('shadow logger unavailable');
        }
    };
    $service = new ShadowScoringTelemetryService(
        $logger,
        app(ScoringTelemetrySampler::class),
    );
    $run = telemetryRun(collect(), collect(), collect());

    expect(fn () => $service->record(
        telemetryCandidate(10),
        $run,
        $run,
        'comparison-123',
    ))->not->toThrow(Throwable::class);
});

it('does not sample away correlated runs or failures', function () {
    config()->set('pembanding_scoring.telemetry.enabled', true);
    config()->set('pembanding_scoring.telemetry.sample_rate', 0);

    $logger = new class extends AbstractLogger
    {
        public array $records = [];

        public function log($level, Stringable|string $message, array $context = []): void
        {
            $this->records[] = compact('level', 'message', 'context');
        }
    };
    $service = new ScoringOperationalTelemetryService(
        $logger,
        app(ScoringMetricsAggregator::class),
        app(ScoringAttributeResolver::class),
        app(ScoringTelemetrySampler::class),
    );
    $reference = telemetryCandidate(10);
    $run = telemetryRun(collect(), collect(), collect());

    $service->record('v2_shadow', $reference, $run, true);
    $service->record('v2_shadow', $reference, $run, true, 'comparison-123');
    $service->recordFailure(
        'v2_shadow',
        $reference,
        'v2',
        new RuntimeException('pipeline unavailable'),
        'comparison-123',
    );

    expect($logger->records)->toHaveCount(2)
        ->and($logger->records[0]['context']['comparison_id'])->toBe('comparison-123')
        ->and($logger->records[1]['message'])->toBe('pembanding_scoring_failure')
        ->and($logger->records[1]['context']['comparison_id'])->toBe('comparison-123');
});
