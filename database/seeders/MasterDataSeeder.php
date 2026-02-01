<?php

namespace Database\Seeders;

use App\Models\BentukTanah;
use App\Models\DokumenTanah;
use App\Models\JenisListing;
use App\Models\JenisObjek;
use App\Models\KondisiTanah;
use App\Models\Peruntukan;
use App\Models\PosisiTanah;
use App\Models\StatusPemberiInformasi;
use App\Models\Topografi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedJenisListing();
            $this->seedBentukTanah();
            $this->seedDokumenTanah();
            $this->seedJenisObjek();
            $this->seedKondisiTanah();
            $this->seedPeruntukan();
            $this->seedPosisiTanah();
            $this->seedStatusPemberiInformasi();
            $this->seedTopografi();
        });
    }

    private function upsertMany(string $modelClass, array $rows): void
    {
        foreach ($rows as $row) {
            /** @var \Illuminate\Database\Eloquent\Model $modelClass */
            $modelClass::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name'       => $row['name'],
                    'sort_order' => $row['sort_order'] ?? 0,
                    'is_active'  => $row['is_active'] ?? true,
                ],
            );
        }
    }

    private function seedJenisListing(): void
    {
        $this->upsertMany(JenisListing::class, [
            [
                'slug' => 'penawaran',
                'name' => 'Penawaran',
                'sort_order' => 1,
                'badge_color' => '#ea580c',
                'marker_icon_url' => 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-orange.png',
            ],
            [
                'slug' => 'transaksi',
                'name' => 'Transaksi',
                'sort_order' => 2,
                'badge_color' => '#16a34a',
                'marker_icon_url' => 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
            ],
            [
                'slug' => 'sewa',
                'name' => 'Sewa',
                'sort_order' => 3,
                'badge_color' => '#0ea5e9',
                'marker_icon_url' => 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
            ],
        ]);
    }

    private function seedBentukTanah(): void
    {
        $this->upsertMany(BentukTanah::class, [
            ['slug' => 'persegi_panjang',  'name' => 'Persegi Panjang',   'sort_order' => 1],
            ['slug' => 'persegi',          'name' => 'Persegi',           'sort_order' => 2],
            ['slug' => 'trapesium',        'name' => 'Trapesium',         'sort_order' => 3],
            ['slug' => 'segitiga',         'name' => 'Segitiga',          'sort_order' => 4],
            ['slug' => 'lingkaran',        'name' => 'Lingkaran',         'sort_order' => 5],
            ['slug' => 'tidak_beraturan',  'name' => 'Tidak Beraturan',   'sort_order' => 6],
            ['slug' => 'letter_l',         'name' => 'Letter L',          'sort_order' => 7],
            ['slug' => 'lainnya',          'name' => 'Lainnya',           'sort_order' => 8],
        ]);
    }

    private function seedDokumenTanah(): void
    {
        $this->upsertMany(DokumenTanah::class, [
            ['slug' => 'sertifikat_hak_milik',          'name' => 'Sertifikat Hak Milik (SHM)',            'sort_order' => 1],
            ['slug' => 'sertifikat_hak_guna_bangunan',  'name' => 'Sertifikat Hak Guna Bangunan (HGB)',    'sort_order' => 2],
            ['slug' => 'sertifikat_hak_guna_usaha',     'name' => 'Sertifikat Hak Guna Usaha (HGU)',       'sort_order' => 3],
            ['slug' => 'akta_jual_beli',                'name' => 'Akta Jual Beli (AJB)',                  'sort_order' => 4],
            ['slug' => 'girik',                         'name' => 'Girik',                                 'sort_order' => 5],
            ['slug' => 'petok_desa',                    'name' => 'Petok D',                               'sort_order' => 6],
            ['slug' => 'surat_camat',                   'name' => 'Surat Camat',                           'sort_order' => 7],
            ['slug' => 'peta_bidang_tanah',             'name' => 'Peta Bidang Tanah (PBT)',               'sort_order' => 8],
            ['slug' => 'lainnya',                       'name' => 'Lainnya',                               'sort_order' => 9],
        ]);
    }

    private function seedJenisObjek(): void
    {
        $this->upsertMany(JenisObjek::class, [
            ['slug' => 'tanah',              'name' => 'Tanah',               'sort_order' => 1],
            ['slug' => 'rumah_tinggal',      'name' => 'Rumah Tinggal',       'sort_order' => 2],
            ['slug' => 'ruko',               'name' => 'Ruko',                'sort_order' => 3],
            ['slug' => 'apartement',         'name' => 'Apartement',          'sort_order' => 4],
            ['slug' => 'kios',               'name' => 'Kios',                'sort_order' => 5],
            ['slug' => 'gudang',             'name' => 'Gudang',              'sort_order' => 6],
            ['slug' => 'kantor',             'name' => 'Kantor',              'sort_order' => 7],
            ['slug' => 'pabrik',             'name' => 'Pabrik',              'sort_order' => 8],
            ['slug' => 'tanah_kebun',        'name' => 'Tanah Kebun',         'sort_order' => 9],
            ['slug' => 'tanah_dan_bangunan', 'name' => 'Tanah dan Bangunan',  'sort_order' => 10],
            ['slug' => 'sawah',              'name' => 'Sawah',               'sort_order' => 11],
        ]);
    }

    private function seedKondisiTanah(): void
    {
        $this->upsertMany(KondisiTanah::class, [
            ['slug' => 'matang',             'name' => 'Matang',              'sort_order' => 1],
            ['slug' => 'rawa',               'name' => 'Rawa',                'sort_order' => 2],
            ['slug' => 'sawah',              'name' => 'Sawah',               'sort_order' => 3],
            ['slug' => 'belum_dikembangkan', 'name' => 'Belum Dikembangkan',  'sort_order' => 4],
            ['slug' => 'lainnya',            'name' => 'Lainnya',             'sort_order' => 5],
        ]);
    }

    private function seedPeruntukan(): void
    {
        $this->upsertMany(Peruntukan::class, [
            ['slug' => 'unit_apartemen', 'name' => 'Unit Apartemen',  'sort_order' => 1],
            ['slug' => 'rumah_tinggal',  'name' => 'Rumah Tinggal',   'sort_order' => 2],
            ['slug' => 'ruko',           'name' => 'Ruko',            'sort_order' => 3],
            ['slug' => 'perkantoran',    'name' => 'Perkantoran',     'sort_order' => 4],
            ['slug' => 'kios',           'name' => 'Kios',            'sort_order' => 5],
            ['slug' => 'gudang',         'name' => 'Gudang',          'sort_order' => 6],
            ['slug' => 'pabrik',         'name' => 'Pabrik',          'sort_order' => 7],
            ['slug' => 'tanah_kosong',   'name' => 'Tanah Kosong',    'sort_order' => 8],
            ['slug' => 'rukan',          'name' => 'Rukan',           'sort_order' => 9],
            ['slug' => 'townhouse',      'name' => 'Town House',      'sort_order' => 10],
            ['slug' => 'villa',          'name' => 'Villa',           'sort_order' => 11],
            ['slug' => 'mall',           'name' => 'Mall',            'sort_order' => 12],
            ['slug' => 'campuran',       'name' => 'Campuran',        'sort_order' => 13],
            ['slug' => 'lainnya',        'name' => 'Lainnya',         'sort_order' => 14],
        ]);
    }

    private function seedPosisiTanah(): void
    {
        $this->upsertMany(PosisiTanah::class, [
            ['slug' => 'kuldesak_lot',  'name' => 'Ujung Jalan (Kuldesak Lot)',           'sort_order' => 1],
            ['slug' => 'interior_lot',  'name' => 'Berada di Tengah (Interior Lot)',      'sort_order' => 2],
            ['slug' => 't_section_lot', 'name' => 'Tusuk Sate (Tusuk Sate)',              'sort_order' => 3],
            ['slug' => 'corner_lot',    'name' => 'Suduk / Hook (Corner Lot)',            'sort_order' => 4],
            ['slug' => 'key_lot',       'name' => 'Mengunci Lot Lain (Key Lot)',          'sort_order' => 5],
            ['slug' => 'flag_lot',      'name' => 'Berbentu Seperti Bendera (Key Lot)',   'sort_order' => 6],
            ['slug' => 'tanpa_akses',   'name' => 'Tanpa Akses Jalan (Helicopter)',       'sort_order' => 7],
        ]);
    }

    private function seedStatusPemberiInformasi(): void
    {
        $this->upsertMany(StatusPemberiInformasi::class, [
            ['slug' => 'agen_properti',      'name' => 'Agen Properti',    'sort_order' => 1],
            ['slug' => 'pemilik_properti',   'name' => 'Pemilik Properti', 'sort_order' => 2],
            ['slug' => 'pihak_ke3',          'name' => 'Pihak Ke 3',       'sort_order' => 3],
            ['slug' => 'perantara',          'name' => 'Perantara',        'sort_order' => 4],
            ['slug' => 'keluarga_pemilik',   'name' => 'Keluarga Pemilik', 'sort_order' => 5],
        ]);
    }

    private function seedTopografi(): void
    {
        $this->upsertMany(Topografi::class, [
            ['slug' => 'datar_lebih_tinggi_dari_jalan',  'name' => 'Datar, Lebih Tinggi Dari Jalan',   'sort_order' => 1],
            ['slug' => 'datar_lebih_rendah_dari_jalan',  'name' => 'Datar, Lebih Rendah Dari Jalan',   'sort_order' => 2],
            ['slug' => 'datar_dengan_jalan',             'name' => 'Datar, Sama dengan Jalan',         'sort_order' => 3],
            ['slug' => 'berbukit',                       'name' => 'Berbukit',                         'sort_order' => 4],
            ['slug' => 'melandai',                       'name' => 'Melandai',                         'sort_order' => 5],
            ['slug' => 'berbukit_dan_melandai',          'name' => 'Berbukit dan Melandai',            'sort_order' => 6],
            ['slug' => 'bervariasi',                     'name' => 'Bervariasi',                       'sort_order' => 7],
            ['slug' => 'lainnya',                        'name' => 'Lainnya',                          'sort_order' => 8],
        ]);
    }
}
