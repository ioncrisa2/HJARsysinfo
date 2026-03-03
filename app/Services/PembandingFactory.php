<?php

namespace App\Services;

use App\Models\Pembanding;

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

        // Dictionary slug fields
        $pembanding->peruntukan = $this->parseSlug($data, 'peruntukan');
        $pembanding->dokumen_tanah = $this->parseSlug($data, 'dokumen_tanah');
        $pembanding->posisi_tanah = $this->parseSlug($data, 'posisi_tanah');
        $pembanding->kondisi_tanah = $this->parseSlug($data, 'kondisi_tanah');

        return $pembanding;
    }

    protected function parseSlug(array $data, string $key): ?string
    {
        if (!array_key_exists($key, $data) || !is_string($data[$key])) {
            return null;
        }

        $value = strtolower(trim($data[$key]));

        return $value !== '' ? $value : null;
    }
}
