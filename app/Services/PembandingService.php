<?php

namespace App\Services;

use App\Models\Pembanding;
use App\Services\Scoring\ScoringOperationalTelemetryService;
use App\Services\Scoring\ScoringPipelineExecutionService;
use App\Services\Scoring\ScoringTelemetrySampler;
use App\Services\Scoring\ShadowScoringTelemetryService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

class PembandingService
{
    public function __construct(
        private readonly ScoringPipelineExecutionService $pipelines,
        private readonly ScoringOperationalTelemetryService $operationalTelemetry,
        private readonly ShadowScoringTelemetryService $shadowTelemetry,
        private readonly ScoringTelemetrySampler $telemetrySampler,
    ) {}

    public function findSimilar(
        Pembanding $input,
        int $limit = 100,
        ?int $radiusMeters = null,
    ): Collection {
        $mode = (string) config('pembanding_scoring.mode', 'v2_shadow');

        if ($mode === 'v2') {
            $v2 = $this->pipelines->runV2($input, $limit, $radiusMeters);
            $this->operationalTelemetry->record($mode, $input, $v2, true);

            return $v2->ranking->results;
        }

        $executeShadow = $mode === 'v2_shadow'
            && $this->telemetrySampler->shadowExecution();
        $comparisonId = $executeShadow ? (string) Str::uuid() : null;

        $legacy = $this->pipelines->runLegacy($input, $limit, $radiusMeters);
        $this->operationalTelemetry->record($mode, $input, $legacy, true, $comparisonId);

        if ($executeShadow) {
            try {
                $v2 = $this->pipelines->runV2($input, $limit, $radiusMeters);
                $this->operationalTelemetry->record($mode, $input, $v2, false, $comparisonId);
                $this->shadowTelemetry->record($input, $legacy, $v2, $comparisonId);
            } catch (Throwable $exception) {
                $this->operationalTelemetry->recordFailure(
                    $mode,
                    $input,
                    'v2',
                    $exception,
                    $comparisonId,
                );
            }
        }

        return $legacy->ranking->results;
    }
}
