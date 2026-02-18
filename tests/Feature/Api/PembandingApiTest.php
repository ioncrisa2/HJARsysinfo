<?php

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
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);

    $this->province = Province::query()->create([
        'id' => '71',
        'name' => 'Sulawesi Utara',
    ]);

    $this->regency = Regency::query()->create([
        'id' => '7101',
        'province_id' => $this->province->id,
        'name' => 'Kabupaten Test',
    ]);

    $this->district = District::query()->create([
        'id' => '710101',
        'regency_id' => $this->regency->id,
        'name' => 'Kecamatan Test',
    ]);

    $this->village = Village::query()->create([
        'id' => '7101011001',
        'district_id' => $this->district->id,
        'name' => 'Kelurahan Test',
    ]);

    $this->refs = [
        'jenis_listing_id' => JenisListing::query()->create([
            'slug' => 'transaksi',
            'name' => 'Transaksi',
        ])->id,
        'jenis_objek_id' => JenisObjek::query()->create([
            'slug' => 'tanah',
            'name' => 'Tanah',
        ])->id,
        'status_pemberi_informasi_id' => StatusPemberiInformasi::query()->create([
            'slug' => 'agen_properti',
            'name' => 'Agen Properti',
        ])->id,
        'bentuk_tanah_id' => BentukTanah::query()->create([
            'slug' => 'persegi_panjang',
            'name' => 'Persegi Panjang',
        ])->id,
        'dokumen_tanah_id' => DokumenTanah::query()->create([
            'slug' => 'sertifikat_hak_milik',
            'name' => 'Sertifikat Hak Milik',
        ])->id,
        'posisi_tanah_id' => PosisiTanah::query()->create([
            'slug' => 'interior_lot',
            'name' => 'Interior Lot',
        ])->id,
        'kondisi_tanah_id' => KondisiTanah::query()->create([
            'slug' => 'matang',
            'name' => 'Matang',
        ])->id,
        'topografi_id' => Topografi::query()->create([
            'slug' => 'datar',
            'name' => 'Datar',
        ])->id,
        'peruntukan_rumah_id' => Peruntukan::query()->create([
            'slug' => 'rumah_tinggal',
            'name' => 'Rumah Tinggal',
        ])->id,
        'peruntukan_tanah_id' => Peruntukan::query()->create([
            'slug' => 'tanah_kosong',
            'name' => 'Tanah Kosong',
        ])->id,
        'peruntukan_ruko_id' => Peruntukan::query()->create([
            'slug' => 'ruko',
            'name' => 'Ruko',
        ])->id,
    ];

    $this->makePembanding = function (array $overrides = []) {
        return Pembanding::query()->create(array_merge([
            'nama_pemberi_informasi' => 'Sumber Data',
            'nomer_telepon_pemberi_informasi' => '08123456789',
            'alamat_data' => 'Jl. Testing No. 1',
            'latitude' => -2.5489,
            'longitude' => 118.0149,
            'luas_tanah' => 120,
            'luas_bangunan' => 60,
            'tahun_bangun' => 2020,
            'lebar_depan' => 8,
            'lebar_jalan' => 6,
            'rasio_tapak' => '2:1',
            'harga' => 500000000,
            'tanggal_data' => now()->toDateString(),
            'catatan' => '-',
            'province_id' => $this->province->id,
            'regency_id' => $this->regency->id,
            'district_id' => $this->district->id,
            'village_id' => $this->village->id,
            'created_by' => $this->user->id,
            'jenis_listing_id' => $this->refs['jenis_listing_id'],
            'jenis_objek_id' => $this->refs['jenis_objek_id'],
            'status_pemberi_informasi_id' => $this->refs['status_pemberi_informasi_id'],
            'bentuk_tanah_id' => $this->refs['bentuk_tanah_id'],
            'dokumen_tanah_id' => $this->refs['dokumen_tanah_id'],
            'posisi_tanah_id' => $this->refs['posisi_tanah_id'],
            'kondisi_tanah_id' => $this->refs['kondisi_tanah_id'],
            'topografi_id' => $this->refs['topografi_id'],
            'peruntukan_id' => $this->refs['peruntukan_rumah_id'],
        ], $overrides));
    };
});

it('returns paginated pembanding list', function () {
    ($this->makePembanding)(['alamat_data' => 'Jl. A']);
    ($this->makePembanding)(['alamat_data' => 'Jl. B', 'latitude' => -2.5495, 'longitude' => 118.0155]);

    $response = $this->getJson('/api/v1/pembandings?limit=1');

    $response
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.per_page', 1);

    expect($response->json('data.data'))->toHaveCount(1);
});

it('applies pembanding filters in index endpoint', function () {
    $rumah = ($this->makePembanding)([
        'alamat_data' => 'Rumah Tinggal',
        'harga' => 180000000,
        'peruntukan_id' => $this->refs['peruntukan_rumah_id'],
    ]);

    ($this->makePembanding)([
        'alamat_data' => 'Ruko Mahal',
        'harga' => 900000000,
        'latitude' => -2.55,
        'longitude' => 118.02,
        'peruntukan_id' => $this->refs['peruntukan_ruko_id'],
    ]);

    $response = $this->getJson('/api/v1/pembandings?peruntukan=rumah_tinggal&min_harga=100000000&max_harga=200000000');

    $response->assertOk();

    expect($response->json('data.data'))->toHaveCount(1)
        ->and($response->json('data.data.0.id'))->toBe($rumah->id);
});

it('returns 422 when index filter is invalid', function () {
    $response = $this->getJson('/api/v1/pembandings?min_harga=500000000&max_harga=100000000');

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['max_harga']);
});

it('returns 422 when similar by id range_km is invalid', function () {
    $record = ($this->makePembanding)();

    $this->getJson("/api/v1/pembandings/{$record->id}/similar?range_km=0")
        ->assertStatus(422)
        ->assertJsonValidationErrors(['range_km']);
});

it('returns detail pembanding by id', function () {
    $record = ($this->makePembanding)(['alamat_data' => 'Detail Record']);

    $this->getJson("/api/v1/pembandings/{$record->id}")
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.id', $record->id)
        ->assertJsonPath('data.peruntukan.slug', 'rumah_tinggal');
});

it('returns not found when detail pembanding id does not exist', function () {
    $this->getJson('/api/v1/pembandings/999999')
        ->assertNotFound()
        ->assertJsonPath('status', 'error');
});

it('returns similar data by id with distance field', function () {
    $input = ($this->makePembanding)([
        'alamat_data' => 'Input Utama',
        'latitude' => -2.5489,
        'longitude' => 118.0149,
        'peruntukan_id' => $this->refs['peruntukan_rumah_id'],
    ]);

    ($this->makePembanding)([
        'alamat_data' => 'Kandidat Terdekat',
        'latitude' => -2.5491,
        'longitude' => 118.0151,
        'peruntukan_id' => $this->refs['peruntukan_tanah_id'],
    ]);

    $response = $this->getJson("/api/v1/pembandings/{$input->id}/similar?limit=5");

    $response
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'score', 'distance', 'priority_rank', 'is_fallback'],
            ],
        ])
        ->assertJsonMissingPath('data.0.sql_distance');
});

it('applies range_km in similar by id endpoint', function () {
    $input = ($this->makePembanding)([
        'alamat_data' => 'Input Range',
        'latitude' => -2.5489,
        'longitude' => 118.0149,
        'peruntukan_id' => $this->refs['peruntukan_rumah_id'],
    ]);

    $near = ($this->makePembanding)([
        'alamat_data' => 'Kandidat Dekat',
        'latitude' => -2.5492,
        'longitude' => 118.0152,
        'peruntukan_id' => $this->refs['peruntukan_tanah_id'],
    ]);

    $far = ($this->makePembanding)([
        'alamat_data' => 'Kandidat Jauh',
        'latitude' => -2.7200,
        'longitude' => 118.2000,
        'peruntukan_id' => $this->refs['peruntukan_tanah_id'],
    ]);

    $response = $this->getJson("/api/v1/pembandings/{$input->id}/similar?range_km=2&limit=10");

    $response
        ->assertOk()
        ->assertJsonPath('status', 'success');

    $ids = collect($response->json('data'))->pluck('id');

    expect($ids)->toContain($near->id)
        ->and($ids)->not->toContain($far->id);
});

it('falls back to nearest candidates when peruntukan match is missing', function () {
    ($this->makePembanding)([
        'alamat_data' => 'Ruko Candidate',
        'latitude' => -2.5491,
        'longitude' => 118.0151,
        'peruntukan_id' => $this->refs['peruntukan_ruko_id'],
    ]);

    $response = $this->postJson('/api/v1/pembandings/similar', [
        'latitude' => -2.5489,
        'longitude' => 118.0149,
        'district_id' => $this->district->id,
        'peruntukan' => 'rumah_tinggal',
        'luas_tanah' => 120,
        'luas_bangunan' => 60,
        'dokumen_tanah' => 'sertifikat_hak_milik',
        'lebar_jalan' => 6,
        'posisi_tanah' => 'interior_lot',
        'kondisi_tanah' => 'matang',
        'harga' => 500000000,
        'limit' => 10,
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.0.is_fallback', true);
});

it('applies range_km in similar by payload endpoint', function () {
    ($this->makePembanding)([
        'alamat_data' => 'Ruko Sangat Jauh',
        'latitude' => -2.7500,
        'longitude' => 118.3500,
        'peruntukan_id' => $this->refs['peruntukan_ruko_id'],
    ]);

    $this->postJson('/api/v1/pembandings/similar', [
        'latitude' => -2.5489,
        'longitude' => 118.0149,
        'district_id' => $this->district->id,
        'peruntukan' => 'rumah_tinggal',
        'luas_tanah' => 120,
        'luas_bangunan' => 60,
        'dokumen_tanah' => 'sertifikat_hak_milik',
        'lebar_jalan' => 6,
        'posisi_tanah' => 'interior_lot',
        'kondisi_tanah' => 'matang',
        'harga' => 500000000,
        'limit' => 10,
        'range_km' => 1,
    ])
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('message', 'Tidak ada data pembanding yang cocok')
        ->assertJsonCount(0, 'data');
});

it('does not use fallback for ruko when there are no ruko candidates', function () {
    ($this->makePembanding)([
        'alamat_data' => 'Rumah Candidate',
        'latitude' => -2.5491,
        'longitude' => 118.0151,
        'peruntukan_id' => $this->refs['peruntukan_rumah_id'],
    ]);

    $this->postJson('/api/v1/pembandings/similar', [
        'latitude' => -2.5489,
        'longitude' => 118.0149,
        'district_id' => $this->district->id,
        'peruntukan' => 'ruko',
        'luas_tanah' => 120,
        'luas_bangunan' => 60,
        'dokumen_tanah' => 'sertifikat_hak_milik',
        'lebar_jalan' => 6,
        'posisi_tanah' => 'interior_lot',
        'kondisi_tanah' => 'matang',
        'harga' => 500000000,
        'limit' => 10,
    ])
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('message', 'Tidak ada data pembanding yang cocok')
        ->assertJsonCount(0, 'data');
});

it('returns 422 when similar payload is invalid', function () {
    $response = $this->postJson('/api/v1/pembandings/similar', [
        'latitude' => 120,
        'longitude' => 118.0149,
        'district_id' => $this->district->id,
        'peruntukan' => 'tidak_valid',
        'dokumen_tanah' => 'tidak_valid',
        'posisi_tanah' => 'tidak_valid',
        'kondisi_tanah' => 'tidak_valid',
        'limit' => 0,
        'range_km' => 0,
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'latitude',
            'peruntukan',
            'dokumen_tanah',
            'posisi_tanah',
            'kondisi_tanah',
            'limit',
            'range_km',
        ]);
});
