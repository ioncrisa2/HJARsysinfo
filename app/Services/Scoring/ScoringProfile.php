<?php

namespace App\Services\Scoring;

use InvalidArgumentException;

class ScoringProfile
{
    public function version(string $method = 'v2'): string
    {
        return (string) config("pembanding_scoring.methods.{$method}.version");
    }

    public function weight(string $component): float
    {
        $weight = config("pembanding_scoring.methods.v2.weights.{$component}");

        if (! is_numeric($weight) || (float) $weight <= 0) {
            throw new InvalidArgumentException("Bobot scoring {$component} tidak valid.");
        }

        return (float) $weight;
    }

    public function setting(string $key, float $default = 0): float
    {
        return (float) config("pembanding_scoring.methods.v2.{$key}", $default);
    }

    public function similarity(string $matrix, string $a, string $b, float $default = 0): float
    {
        if ($a === $b) {
            return 1;
        }

        $direct = config("pembanding_scoring.methods.v2.{$matrix}.{$a}.{$b}");
        $reverse = config("pembanding_scoring.methods.v2.{$matrix}.{$b}.{$a}");
        $value = is_numeric($direct) ? $direct : $reverse;

        if (! is_numeric($value)) {
            return $default;
        }

        return max(0, min(1, (float) $value));
    }

    /**
     * @return list<string>
     */
    public function configuredSubstitutions(string $matrix, string $value): array
    {
        $matrixValues = (array) config("pembanding_scoring.methods.v2.{$matrix}", []);
        $substitutions = array_keys((array) ($matrixValues[$value] ?? []));

        foreach ($matrixValues as $source => $targets) {
            if (array_key_exists($value, (array) $targets)) {
                $substitutions[] = (string) $source;
            }
        }

        return array_values(array_unique($substitutions));
    }
}
