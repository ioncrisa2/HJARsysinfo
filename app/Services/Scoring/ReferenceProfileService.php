<?php

namespace App\Services\Scoring;

use App\Models\Pembanding;

class ReferenceProfileService
{
    public function __construct(private readonly ScoringAttributeResolver $resolver) {}

    /**
     * @return list<string>
     */
    public function applicableComponents(Pembanding $reference): array
    {
        return (array) $this->profileValue(
            $reference,
            'components',
            config('pembanding_scoring.profiles.default.components', []),
        );
    }

    /**
     * @return list<string>
     */
    public function requiredReportFields(Pembanding $reference): array
    {
        return (array) $this->profileValue(
            $reference,
            'report_required_fields',
            config('pembanding_scoring.profiles.default.report_required_fields', []),
        );
    }

    private function profileValue(Pembanding $reference, string $key, mixed $default): mixed
    {
        $objectType = $this->resolver->objectType($reference);

        if (! $objectType) {
            return $default;
        }

        return config("pembanding_scoring.profiles.objects.{$objectType}.{$key}", $default);
    }
}
