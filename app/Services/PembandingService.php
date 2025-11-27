<?php

namespace App\Services;

use App\Enums\Peruntukan;
use App\Models\Pembanding;
use App\Repositories\PembandingRepository;
use Illuminate\Support\Collection;

class PembandingService
{
    protected array $peruntukanToGroup = [];

    public function __construct(
        protected PembandingRepository $repository
    ){}

    public function findSimilar(Pembanding $input, int $limit = 100): Collection
    {
        // SQL pre-filter: radius + district
        $candidates = $this->repository->getGeoCandidates($input, $limit);

        // Score setiap item
        $scored = $candidates->map(function (Pembanding $item) use ($input) {
            $item->score = $this->score($input, $item);
            return $item;
        });

        // Urutkan berdasarkan skor
        return $scored->sortByDesc('score')->values();
    }

     /**
     * =====================
     *  SCORING FUNCTIONS
     * =====================
     */

    protected function score(Pembanding $input, Pembanding $data): float
    {
        $score = 0;

        // Jarak
        $distance = $this->distance(
            $input->latitude, $input->longitude,
            $data->latitude, $data->longitude
        );
        $score += 1000 / ($distance + 1);

        // Zoning
        $score += $this->zoningScore($input->peruntukan, $data->peruntukan) * 10;

        // Luas
        $il = $input->luas_tanah + $input->luas_bangunan;
        $dl = $data->luas_tanah + $data->luas_bangunan;
        if ($il > 0 && $dl > 0) {
            $score += (min($il, $dl) / max($il, $dl)) * 10;
        }

        // Legalitas
        $score += $this->legalScore($input, $data);

        // Lebar jalan
        $score += $this->lebarJalanScore($input, $data);

        // Posisi tanah
        $score += ($input->posisi_tanah === $data->posisi_tanah) ? 5 : 2;

        // Kondisi tanah
        $score += $this->kondisiScore($input, $data);

        // Harga
        if ($input->harga && $data->harga) {
            $score += (min($input->harga, $data->harga) / max($input->harga, $data->harga)) * 10;
        }

        return $score;
    }

    /**
     * Distance (Haversine)
     */
    protected function distance($lat1, $lon1, $lat2, $lon2): float
    {
        $earth = 6371000;
        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $dLat = $lat2 - $lat1;
        $dLon = $lon2 - $lon1;

        $a = sin($dLat / 2) ** 2 +
            cos($lat1) * cos($lat2) *
            sin($dLon / 2) ** 2;

        return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Zoning Group
     */
    protected function zoningScore(?Peruntukan $a, ?Peruntukan $b): int
    {
        if (! $a || ! $b) return 0;

        $this->buildGroups();

        $grpA = $this->peruntukanToGroup[$a->value] ?? null;
        $grpB = $this->peruntukanToGroup[$b->value] ?? null;

        return ($grpA && $grpA === $grpB) ? 3 : 1;
    }

    protected function buildGroups(): void
    {
        if (! empty($this->peruntukanToGroup)) return;

        $groups = [
            'perumahan' => [
                Peruntukan::RumahTinggal,
                Peruntukan::Villa,
                Peruntukan::Townhouse,
                Peruntukan::UnitApartemen,
            ],
            'komersial' => [
                Peruntukan::Ruko,
                Peruntukan::Rukan,
                Peruntukan::Mall,
                Peruntukan::Perkantoran,
                Peruntukan::Kios,
            ],
            'industri' => [
                Peruntukan::Pabrik,
                Peruntukan::Gudang,
            ],
            'campuran' => [
                Peruntukan::TanahKosong,
                Peruntukan::Campuran,
                Peruntukan::Lainnya,
            ],
        ];

        foreach ($groups as $group => $types) {
            foreach ($types as $type) {
                $this->peruntukanToGroup[$type->value] = $group;
            }
        }
    }

    /**
     * Legalitas
     */
    protected function legalScore(Pembanding $a, Pembanding $b): float
    {
        $legal = [
            'shm'      => 3,
            'shbg'     => 2,
            'lainnya'  => 1,
        ];

        $la = strtolower($a->dokumen_tanah?->value ?? 'lainnya');
        $lb = strtolower($b->dokumen_tanah?->value ?? 'lainnya');

        return ($legal[$la] ?? 1) === ($legal[$lb] ?? 1) ? 10 : 5;
    }

    /**
     * Lebar Jalan
     */
    protected function lebarJalanScore(Pembanding $a, Pembanding $b): int
    {
        $diff = abs(($a->lebar_jalan ?? 0) - ($b->lebar_jalan ?? 0));

        return match(true) {
            $diff <= 2 => 8,
            $diff <= 4 => 4,
            default    => 1,
        };
    }

    /**
     * Kondisi tanah
     */
    protected function kondisiScore(Pembanding $a, Pembanding $b): int
    {
        $pri = [
            'matang'            => 3,
            'belum_berkembang'  => 2,
            'sawah'             => 1
        ];

        $ka = strtolower($a->kondisi_tanah?->value ?? 'sawah');
        $kb = strtolower($b->kondisi_tanah?->value ?? 'sawah');

        return ($pri[$ka] ?? 1) === ($pri[$kb] ?? 1) ? 5 : 2;
    }

}
