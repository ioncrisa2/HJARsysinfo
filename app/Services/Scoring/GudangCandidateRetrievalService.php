<?php

namespace App\Services\Scoring;

use App\Models\Pembanding;
use Illuminate\Support\Collection;

class GudangCandidateRetrievalService
{
    public function __construct(private readonly StagedCandidateSearchService $search) {}

    public function retrieve(Pembanding $input, int $poolLimit, ?int $radiusMeters): Collection
    {
        $results = $this->search->search(
            $input,
            $poolLimit,
            ['gudang'],
            radiusMeters: $radiusMeters,
            priority: 1,
        );

        [$minimum, $maximum, $areaMetric] = $this->areaBounds($input);

        if ($results->count() < $poolLimit) {
            $results = $results->merge($this->search->search(
                $input,
                $poolLimit - $results->count(),
                ['tanah_kosong'],
                minTotalArea: $minimum,
                maxTotalArea: $maximum,
                radiusMeters: $radiusMeters,
                priority: 2,
                areaMetric: $areaMetric,
            ));
        }

        if ($results->count() < $poolLimit) {
            $results = $results->merge($this->search->search(
                $input,
                $poolLimit - $results->count(),
                ['campuran'],
                minTotalArea: $minimum,
                maxTotalArea: $maximum,
                radiusMeters: $radiusMeters,
                priority: 3,
                areaMetric: $areaMetric,
            ));
        }

        return $results->unique('id')->values();
    }

    private function areaBounds(Pembanding $input): array
    {
        $area = $input->luas_tanah;
        $metric = 'land';

        if (! is_numeric($area) || (float) $area <= 0) {
            $area = $input->luas_bangunan;
            $metric = 'building';
        }

        if (! is_numeric($area) || (float) $area <= 0) {
            return [null, null, 'total'];
        }

        return [(float) $area * 0.8, (float) $area * 1.25, $metric];
    }
}
