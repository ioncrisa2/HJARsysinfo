<?php

namespace App\Services\Scoring;

use App\DTOs\Scoring\ReportReadinessResult;
use App\Models\Pembanding;

class ReportReadinessService
{
    public function __construct(
        private readonly ScoringAttributeResolver $resolver,
        private readonly ReferenceProfileService $referenceProfile,
    ) {}

    public function evaluate(Pembanding $reference, Pembanding $candidate): ReportReadinessResult
    {
        $required = $this->referenceProfile->requiredReportFields($reference);
        $missing = array_values(array_filter(
            $required,
            fn (string $field): bool => ! $this->hasValue($candidate, $field),
        ));
        $completeness = count($required) === 0
            ? 100
            : ((count($required) - count($missing)) / count($required)) * 100;

        $versionState = [
            'id' => $candidate->getKey(),
            'updated_at' => $candidate->updated_at?->toISOString(),
        ];

        foreach ($required as $field) {
            $versionState[$field] = $this->value($candidate, $field);
        }

        return new ReportReadinessResult(
            status: $missing === [] ? 'ready' : 'incomplete',
            completeness: round($completeness, 2),
            missingFields: $missing,
            recordVersion: hash('sha256', json_encode(
                $versionState,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE,
            )),
        );
    }

    private function hasValue(Pembanding $candidate, string $field): bool
    {
        $value = $this->value($candidate, $field);

        if (is_string($value)) {
            return trim($value) !== '';
        }

        return $value !== null;
    }

    private function value(Pembanding $candidate, string $field): mixed
    {
        return match ($field) {
            'jenis_listing' => $this->resolver->listing($candidate),
            'jenis_objek' => $this->resolver->objectType($candidate),
            'peruntukan' => $this->resolver->peruntukan($candidate),
            default => $candidate->getAttribute($field),
        };
    }
}
