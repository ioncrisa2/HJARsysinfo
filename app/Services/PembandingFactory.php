<?php

namespace App\Services;

use App\Models\Pembanding;

class PembandingFactory
{
    public function createFromArray(array $data): Pembanding
    {
        $pembanding = new Pembanding;

        $pembanding->latitude = $data['latitude'];
        $pembanding->longitude = $data['longitude'];
        $pembanding->district_id = $data['district_id'];
        $pembanding->regency_id = $data['regency_id'] ?? null;

        // Missing data must stay null; explicit zero remains a valid value.
        $pembanding->luas_tanah = $data['luas_tanah'] ?? null;
        $pembanding->luas_bangunan = $data['luas_bangunan'] ?? null;
        $pembanding->lebar_jalan = $data['lebar_jalan'] ?? null;
        $pembanding->harga = $data['harga'] ?? null;
        $pembanding->market_basis = $data['market_basis'] ?? null;
        $pembanding->evidence_type = $data['evidence_type'] ?? null;
        $pembanding->reference_date = $data['reference_date'] ?? null;

        // Dictionary slug fields
        $pembanding->peruntukan = $this->parseSlug($data, 'peruntukan');
        $pembanding->jenis_objek = $this->parseSlug($data, 'jenis_objek');
        $pembanding->dokumen_tanah = $this->parseSlug($data, 'dokumen_tanah');
        $pembanding->posisi_tanah = $this->parseSlug($data, 'posisi_tanah');
        $pembanding->kondisi_tanah = $this->parseSlug($data, 'kondisi_tanah');

        return $pembanding;
    }

    protected function parseSlug(array $data, string $key): ?string
    {
        if (! array_key_exists($key, $data) || ! is_string($data[$key])) {
            return null;
        }

        $value = strtolower(trim($data[$key]));

        return $value !== '' ? $value : null;
    }
}
