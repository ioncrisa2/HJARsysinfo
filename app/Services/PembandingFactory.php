<?php

namespace App\Services;

use App\Models\Pembanding;
use App\Models\Peruntukan;
use App\Models\DokumenTanah;
use App\Models\PosisiTanah;
use App\Models\KondisiTanah;

class PembandingFactory
{
    public function createFromArray(array $data): Pembanding
    {
        $pembanding = new Pembanding();

        $pembanding->latitude = $data['latitude'];
        $pembanding->longitude = $data['longitude'];
        $pembanding->district_id = $data['district_id'];

        // Numeric fields with defaults
        $pembanding->luas_tanah = $data['luas_tanah'] ?? 0;
        $pembanding->luas_bangunan = $data['luas_bangunan'] ?? 0;
        $pembanding->lebar_jalan = $data['lebar_jalan'] ?? 0;
        $pembanding->harga = $data['harga'] ?? null;

        // Master data foreign keys (lookup by slug)
        $pembanding->peruntukan_id = $this->lookupMasterId($data, 'peruntukan', Peruntukan::class);
        $pembanding->dokumen_tanah_id = $this->lookupMasterId($data, 'dokumen_tanah', DokumenTanah::class);
        $pembanding->posisi_tanah_id = $this->lookupMasterId($data, 'posisi_tanah', PosisiTanah::class);
        $pembanding->kondisi_tanah_id = $this->lookupMasterId($data, 'kondisi_tanah', KondisiTanah::class);

        return $pembanding;
    }

    /**
     * Look up master data ID by slug value
     */
    protected function lookupMasterId(array $data, string $key, string $modelClass): ?int
    {
        if (!isset($data[$key])) {
            return null;
        }

        $slug = $data[$key];

        static $cache = [];
        $cacheKey = $modelClass . ':' . $slug;

        if (!isset($cache[$cacheKey])) {
            $cache[$cacheKey] = $modelClass::where('slug', $slug)->value('id');
        }

        return $cache[$cacheKey];
    }
}
