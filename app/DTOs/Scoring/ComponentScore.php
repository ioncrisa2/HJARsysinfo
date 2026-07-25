<?php

namespace App\DTOs\Scoring;

final readonly class ComponentScore
{
    public function __construct(
        public string $name,
        public float $weight,
        public bool $applicable,
        public bool $candidateAvailable,
        public ?float $similarity,
        public float $contribution,
        public ?string $warning = null,
        public array $context = [],
    ) {}

    public static function notApplicable(string $name, float $weight): self
    {
        return new self($name, $weight, false, false, null, 0);
    }

    public static function missingCandidate(string $name, float $weight): self
    {
        return new self(
            $name,
            $weight,
            true,
            false,
            0,
            0,
            "Kandidat tidak memiliki data {$name}.",
        );
    }

    public static function scored(
        string $name,
        float $weight,
        float $similarity,
        array $context = [],
    ): self {
        $normalized = max(0, min(1, $similarity));

        return new self(
            $name,
            $weight,
            true,
            true,
            $normalized,
            $weight * $normalized,
            context: $context,
        );
    }

    public function toArray(): array
    {
        return [
            'weight' => round($this->weight, 4),
            'applicable' => $this->applicable,
            'candidate_available' => $this->candidateAvailable,
            'similarity' => $this->similarity === null ? null : round($this->similarity, 6),
            'contribution' => round($this->contribution, 6),
            'warning' => $this->warning,
            'context' => $this->context,
        ];
    }
}
