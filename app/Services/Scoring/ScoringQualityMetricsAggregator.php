<?php

namespace App\Services\Scoring;

use App\Models\Pembanding;
use Illuminate\Support\Collection;

class ScoringQualityMetricsAggregator
{
    public function summarize(Collection $scored): array
    {
        $coverages = $scored->pluck('score_coverage')->filter(
            fn ($value): bool => is_numeric($value),
        )->map(
            fn ($value): float => (float) $value,
        );

        return [
            'reference_coverage' => $this->firstNumeric($scored, 'reference_coverage'),
            'score_coverage' => $this->distribution($coverages),
            'scoring_status_counts' => $this->counts($scored, 'scoring_status'),
            'eligibility_tier_counts' => $this->counts($scored, 'eligibility_tier'),
            'evidence_tier_counts' => $this->counts($scored, 'evidence_tier'),
            'report_readiness_counts' => $this->counts($scored, 'report_readiness'),
            'missing_component_counts' => $this->missingComponents($scored),
            'report_missing_field_counts' => $this->missingReportFields($scored),
        ];
    }

    private function counts(Collection $items, string $attribute): array
    {
        return $items->pluck($attribute)
            ->filter(fn ($value): bool => is_string($value) && $value !== '')
            ->countBy()->sortKeys()->all();
    }

    private function distribution(Collection $values): array
    {
        if ($values->isEmpty()) {
            return ['minimum' => null, 'average' => null, 'maximum' => null];
        }

        return [
            'minimum' => round((float) $values->min(), 2),
            'average' => round((float) $values->average(), 2),
            'maximum' => round((float) $values->max(), 2),
        ];
    }

    private function firstNumeric(Collection $items, string $attribute): ?float
    {
        $value = $items->pluck($attribute)->first(
            fn ($item): bool => is_numeric($item),
        );

        return is_numeric($value) ? round((float) $value, 2) : null;
    }

    private function missingComponents(Collection $items): array
    {
        return $items->flatMap(fn (Pembanding $candidate): array => collect(
            (array) ($candidate->component_scores ?? []),
        )->filter(
            fn (array $component): bool => ($component['applicable'] ?? false)
                && ! ($component['candidate_available'] ?? false),
        )->keys()->all())->countBy()->sortKeys()->all();
    }

    private function missingReportFields(Collection $items): array
    {
        return $items->flatMap(
            fn (Pembanding $candidate): array => (array) ($candidate->report_missing_fields ?? []),
        )->countBy()->sortKeys()->all();
    }
}
