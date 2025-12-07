<?php

namespace App\Services;

use App\Models\Pembanding;
use App\Enums\Peruntukan;
use App\Enums\DokumenTanah;
use App\Enums\PosisiTanah;
use App\Enums\KondisiTanah;

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

        // Enum fields
        $pembanding->peruntukan = $this->parseEnum($data, 'peruntukan', Peruntukan::class);
        $pembanding->dokumen_tanah = $this->parseEnum($data, 'dokumen_tanah', DokumenTanah::class);
        $pembanding->posisi_tanah = $this->parseEnum($data, 'posisi_tanah', PosisiTanah::class);
        $pembanding->kondisi_tanah = $this->parseEnum($data, 'kondisi_tanah', KondisiTanah::class);

        return $pembanding;
    }

    protected function parseEnum(array $data, string $key, string $enumClass): mixed
    {
        if (!isset($data[$key])) {
            return null;
        }

        try {
            return $enumClass::from($data[$key]);
        } catch (\ValueError) {
            return null;
        }
    }
}
