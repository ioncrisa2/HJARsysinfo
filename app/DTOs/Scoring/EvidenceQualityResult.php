<?php

namespace App\DTOs\Scoring;

final readonly class EvidenceQualityResult
{
    /**
     * @param  array<string, mixed>  $components
     * @param  list<string>  $warnings
     */
    public function __construct(
        public float $score,
        public string $tier,
        public array $components,
        public array $warnings = [],
    ) {}

    public function toArray(): array
    {
        return [
            'score' => round($this->score, 2),
            'components' => $this->components,
        ];
    }
}
