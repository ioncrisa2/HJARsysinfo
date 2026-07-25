<?php

namespace App\DTOs\Scoring;

final readonly class SimilarityResult
{
    /**
     * @param  array<string, ComponentScore>  $components
     * @param  list<string>  $warnings
     */
    public function __construct(
        public float $score,
        public ?float $referenceCoverage,
        public ?float $scoreCoverage,
        public string $status,
        public string $methodVersion,
        public array $components = [],
        public array $warnings = [],
    ) {}

    public function componentPayload(): array
    {
        return array_map(
            static fn (ComponentScore $component): array => $component->toArray(),
            $this->components,
        );
    }
}
