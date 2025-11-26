<?php

namespace App\Models\Traits;

use App\Models\Pembanding;
use App\Enums\Peruntukan;

trait PembandingFilterTrait
{
    /**
     * Static cache for Peruntukan group mapping.
     */
    protected static array $peruntukanToGroup = [];

    /**
     * Build Peruntukan group mapping for fast group checks.
     */
    protected static function buildPeruntukanToGroup()
    {
        if (empty(self::$peruntukanToGroup)) {
            $zoningGroup = [
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
            foreach ($zoningGroup as $group => $types) {
                foreach ($types as $type) {
                    self::$peruntukanToGroup[$type->value] = $group;
                }
            }
        }
    }

    /**
     * Calculate Haversine distance (meters) between two [lat, lng] pairs.
     */
    protected static function calculateDistance(
        float $latFrom, float $lngFrom, float $latTo, float $lngTo
    ): float {
        $earthRadius = 6371000;
        $latFromRad = deg2rad($latFrom);
        $lngFromRad = deg2rad($lngFrom);
        $latToRad   = deg2rad($latTo);
        $lngToRad   = deg2rad($lngTo);

        $latDelta = $latToRad - $latFromRad;
        $lngDelta = $lngToRad - $lngFromRad;

        $a = sin($latDelta / 2) ** 2 +
            cos($latFromRad) * cos($latToRad) *
            sin($lngDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Scoring based on zoning group similarity.
     */
    protected function zoningScore(Peruntukan $input, Peruntukan $data): int
    {
        self::buildPeruntukanToGroup();
        $groupA = self::$peruntukanToGroup[$input->value] ?? null;
        $groupB = self::$peruntukanToGroup[$data->value] ?? null;
        return ($groupA && $groupA === $groupB) ? 3 : 1;
    }

    /**
     * Hitung skor pembanding secara lengkap.
     */
    public function scorePembanding(Pembanding $input, Pembanding $data): float
    {
        $score = 0;

        // 1. Jarak
        $distance = self::calculateDistance(
            $input->latitude, $input->longitude,
            $data->latitude, $data->longitude
        );
        $score += 1000 / ($distance + 1); // semakin dekat, skor tinggi

        // 2. Zoning / Peruntukan
        $score += $this->zoningScore($input->peruntukan, $data->peruntukan) * 10;

        // 3. Luas Tanah/Bangunan
        $inputLuas = $input->luas_tanah + $input->luas_bangunan;
        $dataLuas  = $data->luas_tanah + $data->luas_bangunan;
        $score += ($inputLuas > 0 && $dataLuas > 0)
            ? (min($inputLuas, $dataLuas) / max($inputLuas, $dataLuas)) * 10
            : 0;

        // 4. Legalitas
        $legalPriority = [
            'shm'  => 3,
            'shbg' => 2,
            'lainnya' => 1,
        ];
        $inputLegal = mb_strtolower($input->dokumen_tanah->value ?? 'lainnya');
        $dataLegal  = mb_strtolower($data->dokumen_tanah->value ?? 'lainnya');
        $score += ($legalPriority[$inputLegal] ?? 1) === ($legalPriority[$dataLegal] ?? 1) ? 10 : 5;

        // 5. Lebar jalan
        $score += min($input->lebar_jalan, $data->lebar_jalan);

        // 6. Posisi tanah
        $score += ($input->posisi_tanah === $data->posisi_tanah) ? 5 : 2;

        // 7. Kondisi tanah
        $kondisiPriority = [
            'matang' => 3,
            'belum_berkembang' => 2,
            'sawah' => 1,
        ];
        $inputK = mb_strtolower($input->kondisi_tanah->value ?? 'sawah');
        $dataK  = mb_strtolower($data->kondisi_tanah->value ?? 'sawah');
        $score += ($kondisiPriority[$inputK] ?? 1) === ($kondisiPriority[$dataK] ?? 1) ? 5 : 2;

        return $score;
    }

    /**
     * Ambil daftar pembanding terurut berdasarkan scoring, SQL pre-filter by geo and limit.
     */
    public function getFilteredPembanding(Pembanding $input, int $limit = 1000)
    {
        $lat = $input->latitude;
        $lng = $input->longitude;
        $radius = 6371000;
        $maxDistance = 10000; // meters, set this as needed

        // geographic bounding box in degrees
        $latRange = $maxDistance / 111320;
        $lngRange = $maxDistance / (111320 * cos(deg2rad($lat)));

        $minLat = $lat - $latRange;
        $maxLat = $lat + $latRange;
        $minLng = $lng - $lngRange;
        $maxLng = $lng + $lngRange;

        $candidates = Pembanding::selectRaw('pembandings.*,
            ('.$radius.' *
                ACOS(
                    LEAST(
                        1.0,
                        COS(RADIANS(?)) * COS(RADIANS(latitude))
                        * COS(RADIANS(longitude) - RADIANS(?))
                        + SIN(RADIANS(?)) * SIN(RADIANS(latitude))
                    )
                )
            ) as sql_distance', [$lat, $lng, $lat])
            ->where('district_id', $input->district_id)
            ->whereBetween('latitude', [$minLat, $maxLat])
            ->whereBetween('longitude', [$minLng, $maxLng])
            ->orderBy('sql_distance')
            ->limit($limit)
            ->get();

        // Custom PHP scoring
        $scored = $candidates->map(function ($item) use ($input) {
            $item->score = $this->scorePembanding($input, $item);
            return $item;
        });

        return $scored->sortByDesc('score')->values();
    }
}
