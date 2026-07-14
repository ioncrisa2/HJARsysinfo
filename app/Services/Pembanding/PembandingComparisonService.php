<?php

namespace App\Services\Pembanding;

use App\Models\BentukTanah;
use App\Models\District;
use App\Models\DokumenTanah;
use App\Models\JenisListing;
use App\Models\JenisObjek;
use App\Models\KondisiTanah;
use App\Models\Pembanding;
use App\Models\Peruntukan;
use App\Models\PosisiTanah;
use App\Models\Province;
use App\Models\Regency;
use App\Models\StatusPemberiInformasi;
use App\Models\Topografi;
use App\Models\Village;

class PembandingComparisonService
{
    private const FIELDS = [
        'jenis_listing_id' => 'Jenis listing',
        'jenis_objek_id' => 'Jenis objek',
        'nama_pemberi_informasi' => 'Nama pemberi informasi',
        'nomer_telepon_pemberi_informasi' => 'Nomor telepon',
        'status_pemberi_informasi_id' => 'Status pemberi informasi',
        'tanggal_data' => 'Tanggal data',
        'alamat_data' => 'Alamat',
        'province_id' => 'Provinsi',
        'regency_id' => 'Kabupaten/Kota',
        'district_id' => 'Kecamatan',
        'village_id' => 'Desa/Kelurahan',
        'latitude' => 'Latitude',
        'longitude' => 'Longitude',
        'luas_tanah' => 'Luas tanah (m²)',
        'luas_bangunan' => 'Luas bangunan (m²)',
        'tahun_bangun' => 'Tahun bangun',
        'lebar_depan' => 'Lebar depan (m)',
        'lebar_jalan' => 'Lebar jalan (m)',
        'rasio_tapak' => 'Rasio tapak',
        'bentuk_tanah_id' => 'Bentuk tanah',
        'posisi_tanah_id' => 'Posisi tanah',
        'kondisi_tanah_id' => 'Kondisi tanah',
        'topografi_id' => 'Topografi',
        'dokumen_tanah_id' => 'Dokumen tanah',
        'peruntukan_id' => 'Peruntukan',
        'harga' => 'Harga',
        'jangka_waktu_sewa' => 'Jangka waktu sewa',
        'satuan_waktu_sewa' => 'Satuan waktu sewa',
        'catatan' => 'Catatan',
    ];

    private const LOOKUPS = [
        'jenis_listing_id' => JenisListing::class,
        'jenis_objek_id' => JenisObjek::class,
        'status_pemberi_informasi_id' => StatusPemberiInformasi::class,
        'province_id' => Province::class,
        'regency_id' => Regency::class,
        'district_id' => District::class,
        'village_id' => Village::class,
        'bentuk_tanah_id' => BentukTanah::class,
        'posisi_tanah_id' => PosisiTanah::class,
        'kondisi_tanah_id' => KondisiTanah::class,
        'topografi_id' => Topografi::class,
        'dokumen_tanah_id' => DokumenTanah::class,
        'peruntukan_id' => Peruntukan::class,
    ];

    /** @return array<int, array{key: string, label: string, value: string}> */
    public function rows(array|Pembanding $source): array
    {
        $attributes = $source instanceof Pembanding ? $source->getAttributes() : $source;
        $lookupValues = $this->lookupValues($attributes);

        return collect(self::FIELDS)->map(function (string $label, string $key) use ($attributes, $lookupValues): array {
            $value = $lookupValues[$key] ?? $attributes[$key] ?? null;

            return ['key' => $key, 'label' => $label, 'value' => $this->format($key, $value)];
        })->values()->all();
    }

    /** @return array<string, string> */
    private function lookupValues(array $attributes): array
    {
        $resolved = [];

        foreach (self::LOOKUPS as $field => $model) {
            $id = $attributes[$field] ?? null;
            $resolved[$field] = $id === null ? '—' : (string) ($model::query()->whereKey($id)->value('name') ?? $id);
        }

        return $resolved;
    }

    private function format(string $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if ($field === 'harga') {
            return 'Rp '.number_format((float) $value, 0, ',', '.');
        }

        if (in_array($field, ['latitude', 'longitude'], true)) {
            return number_format((float) $value, 6, ',', '.');
        }

        if (in_array($field, ['luas_tanah', 'luas_bangunan', 'lebar_depan', 'lebar_jalan', 'jangka_waktu_sewa'], true)) {
            return rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
        }

        return (string) $value;
    }
}
