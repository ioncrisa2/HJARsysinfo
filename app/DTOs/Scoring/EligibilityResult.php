<?php

namespace App\DTOs\Scoring;

final readonly class EligibilityResult
{
    /**
     * @param  list<string>  $reasons
     * @param  list<string>  $warnings
     */
    public function __construct(
        public bool $eligible,
        public string $tier,
        public array $reasons = [],
        public array $warnings = [],
    ) {}

    public function tierRank(): int
    {
        return match ($this->tier) {
            'primary' => 1,
            'secondary' => 2,
            'fallback' => 3,
            default => 99,
        };
    }
}
