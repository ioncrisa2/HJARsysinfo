<?php

namespace App\Services\Scoring;

class ScoringTelemetrySampler
{
    public function operational(): bool
    {
        return $this->sampled(
            (bool) config('pembanding_scoring.telemetry.enabled', true),
            (float) config('pembanding_scoring.telemetry.sample_rate', 1),
        );
    }

    public function comparisonOperational(): bool
    {
        return (bool) config('pembanding_scoring.telemetry.enabled', true);
    }

    public function failure(): bool
    {
        return (bool) config('pembanding_scoring.telemetry.enabled', true);
    }

    public function shadowExecution(): bool
    {
        return $this->sampled(
            (bool) config('pembanding_scoring.shadow_execution.enabled', true),
            (float) config('pembanding_scoring.shadow_execution.sample_rate', 0.1),
        );
    }

    public function shadowLog(): bool
    {
        return (bool) config('pembanding_scoring.shadow_log_enabled', true);
    }

    private function sampled(bool $enabled, float $rate): bool
    {
        if (! $enabled || $rate <= 0) {
            return false;
        }

        if ($rate >= 1) {
            return true;
        }

        return random_int(1, 10_000) <= (int) round($rate * 10_000);
    }
}
