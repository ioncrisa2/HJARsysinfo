<?php

namespace App\Services\Scoring;

use App\Models\District;
use App\Models\Pembanding;

class AdministrativeAreaResolver
{
    public function regencyId(Pembanding $reference): ?string
    {
        if ($reference->regency_id) {
            return (string) $reference->regency_id;
        }

        if (! $reference->district_id) {
            return null;
        }

        return District::query()
            ->whereKey($reference->district_id)
            ->value('regency_id');
    }
}
