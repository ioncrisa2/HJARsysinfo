<?php

namespace App\DTOs\Scoring;

final readonly class ReportReadinessResult
{
    /**
     * @param  list<string>  $missingFields
     */
    public function __construct(
        public string $status,
        public float $completeness,
        public array $missingFields,
        public ?string $recordVersion,
    ) {}
}
