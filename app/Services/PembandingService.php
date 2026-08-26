<?php

namespace App\Services;

use App\Models\Pembanding;
use App\Services\Scoring\ScoringOperationalTelemetryService;
use App\Services\Scoring\ScoringPipelineExecutionService;
use Illuminate\Support\Collection;

class PembandingService
{
    public function __construct(
        private readonly ScoringPipelineExecutionService $pipelines,
        private readonly ScoringOperationalTelemetryService $operationalTelemetry,
    ) {}

    public function findSimilar(
        Pembanding $input,
        int $limit = 100,
        ?int $radiusMeters = null,
    ): Collection {
        $run = $this->pipelines->run($input, $limit, $radiusMeters);
        $this->operationalTelemetry->record('v2', $input, $run, true);

        return $run->ranking->results;
    }
}
