<?php

namespace App\Services\Scoring;

use App\DTOs\Scoring\ScoringPipelineRun;
use App\Models\Pembanding;
use Illuminate\Container\Attributes\Log;
use Psr\Log\LoggerInterface;
use Throwable;

class ScoringOperationalTelemetryService
{
    public function __construct(
        #[Log('scoring')] private readonly LoggerInterface $logger,
        private readonly ScoringMetricsAggregator $metrics,
        private readonly ScoringAttributeResolver $resolver,
        private readonly ScoringTelemetrySampler $sampler,
    ) {}

    public function record(
        string $mode,
        Pembanding $reference,
        ScoringPipelineRun $run,
        bool $activeResponse,
        ?string $comparisonId = null,
    ): void {
        try {
            $shouldRecord = $comparisonId === null
                ? $this->sampler->operational()
                : $this->sampler->comparisonOperational();

            if (! $shouldRecord) {
                return;
            }

            $this->logger->info('pembanding_scoring_run', array_merge(
                $this->context($mode, $reference, $comparisonId),
                ['active_response' => $activeResponse],
                $this->metrics->summarize($run),
            ));
        } catch (Throwable) {
            // Operational telemetry must never alter scoring responses.
        }
    }

    public function recordFailure(
        string $mode,
        Pembanding $reference,
        string $pipeline,
        Throwable $exception,
        ?string $comparisonId = null,
    ): void {
        try {
            if (! $this->sampler->failure()) {
                return;
            }

            $this->logger->warning('pembanding_scoring_failure', array_merge(
                $this->context($mode, $reference, $comparisonId),
                [
                    'pipeline' => $pipeline,
                    'failure_class' => $exception::class,
                    'failure_code' => (string) $exception->getCode(),
                ],
            ));
        } catch (Throwable) {
            // Failure reporting is best-effort.
        }
    }

    private function context(
        string $mode,
        Pembanding $reference,
        ?string $comparisonId,
    ): array {
        $context = [
            'event_schema_version' => 2,
            'mode' => $mode,
            'reference_source' => $reference->exists ? 'stored' : 'payload',
            'market_basis' => $this->resolver->marketBasis($reference),
            'object_type' => $this->resolver->objectType($reference),
            'peruntukan' => $this->resolver->peruntukan($reference),
        ];

        if ($comparisonId !== null) {
            $context['comparison_id'] = $comparisonId;
        }

        return $context;
    }
}
