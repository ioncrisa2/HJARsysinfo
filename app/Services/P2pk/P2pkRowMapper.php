<?php

namespace App\Services\P2pk;

use App\Models\JenisListing;
use App\Models\JenisObjek;
use App\Models\Peruntukan;
use App\Models\StatusPemberiInformasi;

class P2pkRowMapper
{
    private const OBJECT_MAP = [
        'TANAH KOSONG' => ['jenis_objek' => 'tanah', 'peruntukan' => 'tanah_kosong'],
        'RUMAH TINGGAL' => ['jenis_objek' => 'rumah_tinggal', 'peruntukan' => 'rumah_tinggal'],
        'RUKO' => ['jenis_objek' => 'ruko', 'peruntukan' => 'ruko'],
        'GUDANG' => ['jenis_objek' => 'gudang', 'peruntukan' => 'gudang'],
    ];

    private const REQUIRED_LABELS = [
        'jenis_listing_id' => 'Jenis listing', 'jenis_objek_id' => 'Jenis objek',
        'nama_pemberi_informasi' => 'Nama pemberi informasi', 'alamat_data' => 'Alamat',
        'province_id' => 'Provinsi', 'regency_id' => 'Kabupaten/Kota',
        'district_id' => 'Kecamatan', 'village_id' => 'Desa/Kelurahan',
        'latitude' => 'Latitude', 'longitude' => 'Longitude', 'luas_tanah' => 'Luas tanah',
        'lebar_depan' => 'Lebar depan', 'lebar_jalan' => 'Lebar jalan',
        'bentuk_tanah_id' => 'Bentuk tanah', 'posisi_tanah_id' => 'Posisi tanah',
        'kondisi_tanah_id' => 'Kondisi tanah', 'topografi_id' => 'Topografi',
        'dokumen_tanah_id' => 'Dokumen tanah', 'peruntukan_id' => 'Peruntukan',
        'harga' => 'Harga', 'image' => 'Gambar', 'luas_bangunan' => 'Luas bangunan',
        'tahun_bangun' => 'Tahun bangun',
    ];

    public function __construct(
        private readonly P2pkValueNormalizer $normalizer,
        private readonly P2pkLocationResolver $locations,
    ) {}

    /** @return array{mapped: array<string, mixed>, missing: array<int, array{field: string, label: string}>, warnings: array<int, array{field: string, message: string}>} */
    public function map(array $source): array
    {
        $warnings = [];
        $objectMapping = self::OBJECT_MAP[$this->normalizer->key($source['Jenis Pembanding'] ?? null)] ?? null;
        $jenisObjek = $this->masterId(JenisObjek::class, $objectMapping['jenis_objek'] ?? null);
        $peruntukan = $this->masterId(Peruntukan::class, $objectMapping['peruntukan'] ?? null);
        $listing = $this->masterId(JenisListing::class, strtolower($this->normalizer->key($source['Transaksi Penawaran'] ?? null)));
        $statusSource = $this->normalizer->key($source['Sumber Data'] ?? null) === 'PEMILIK'
            ? $this->masterId(StatusPemberiInformasi::class, 'pemilik_properti')
            : null;

        if (! $objectMapping) {
            $warnings[] = ['field' => 'jenis_objek_id', 'message' => 'Jenis pembanding belum dikenali. Pilih jenis objek yang benar.'];
        }

        if (! $listing) {
            $warnings[] = ['field' => 'jenis_listing_id', 'message' => 'Jenis transaksi belum dikenali. Pilih jenis listing yang benar.'];
        }

        $coordinates = $this->normalizer->coordinates($source['Koordinat'] ?? null);
        if (! $coordinates) {
            $warnings[] = ['field' => 'coordinates', 'message' => 'Koordinat kosong atau tidak benar. Isi lokasi aset yang benar.'];
        }

        $location = $this->locations->resolve($source);
        $mapped = [
            'source_report_number' => $this->normalizer->text($source['Nomor Laporan Penilaian'] ?? null),
            'jenis_listing_id' => $listing,
            'jenis_objek_id' => $jenisObjek,
            'status_pemberi_informasi_id' => $statusSource,
            'peruntukan_id' => $peruntukan,
            'nama_pemberi_informasi' => $this->normalizer->text($source['Sumber Data Lainnya'] ?? null),
            'nomer_telepon_pemberi_informasi' => $this->normalizer->text($source['Kontak Sumber Data'] ?? null),
            'alamat_data' => $this->address($source),
            ...$location['mapped'],
            'latitude' => $coordinates['latitude'] ?? null,
            'longitude' => $coordinates['longitude'] ?? null,
            'luas_tanah' => $this->normalizer->number($source['Luas Tanah'] ?? null),
            'luas_bangunan' => $this->normalizer->number($source['Luas Bangunan'] ?? null),
            'harga' => $this->normalizer->number($source['Harga'] ?? null),
            'source_metadata' => [
                'bulan_tahun' => $this->normalizer->text($source['Bulan Tahun'] ?? null),
                'indikasi_nilai' => $this->normalizer->number($source['Indikasi Nilai'] ?? null),
                'sumber_data' => $this->normalizer->text($source['Sumber Data'] ?? null),
            ],
        ];

        $required = array_keys(self::REQUIRED_LABELS);
        if ($this->normalizer->key($source['Jenis Pembanding'] ?? null) === 'TANAH KOSONG') {
            $required = array_values(array_diff($required, ['luas_bangunan', 'tahun_bangun']));
        }

        $missing = collect($required)
            ->filter(fn (string $field): bool => ! array_key_exists($field, $mapped) || $mapped[$field] === null || $mapped[$field] === '')
            ->map(fn (string $field): array => ['field' => $field, 'label' => self::REQUIRED_LABELS[$field]])
            ->values()
            ->all();

        return [
            'mapped' => $mapped,
            'missing' => $missing,
            'warnings' => [...$warnings, ...$location['warnings']],
        ];
    }

    private function masterId(string $model, ?string $slug): ?int
    {
        if (! $slug) {
            return null;
        }

        return $model::query()->where('slug', $slug)->where('is_active', true)->value('id');
    }

    private function address(array $source): ?string
    {
        $address = $this->normalizer->text($source['Alamat'] ?? null);
        $rtRw = $this->normalizer->text($source['RT/RW'] ?? null);

        return collect([$address, $rtRw ? "RT/RW {$rtRw}" : null])->filter()->implode(', ') ?: null;
    }
}
