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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $permissions = [
        'view_any_data::pembanding',
        'view_data::pembanding',
        'create_data::pembanding',
        'update_data::pembanding',
    ];

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->user->givePermissionTo($permissions);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

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

    $this->validPembandingPayload = function (array $overrides = []) {
        return array_merge([
            'nama_pemberi_informasi' => 'Test User',
            'nomer_telepon_pemberi_informasi' => '08123456789',
            'alamat_data' => 'Jl. Test Create',
            'latitude' => -2.5,
            'longitude' => 118.0,
            'luas_tanah' => 100,
            'lebar_depan' => 10,
            'lebar_jalan' => 5,
            'harga' => 100000000,
            'tanggal_data' => now()->toDateString(),
            'province_id' => $this->province->id,
            'regency_id' => $this->regency->id,
            'district_id' => $this->district->id,
            'village_id' => $this->village->id,
            'jenis_listing_id' => $this->refs['jenis_listing_id'],
            'jenis_objek_id' => $this->refs['jenis_objek_id'],
            'status_pemberi_informasi_id' => $this->refs['status_pemberi_informasi_id'],
            'bentuk_tanah_id' => $this->refs['bentuk_tanah_id'],
            'dokumen_tanah_id' => $this->refs['dokumen_tanah_id'],
            'posisi_tanah_id' => $this->refs['posisi_tanah_id'],
            'kondisi_tanah_id' => $this->refs['kondisi_tanah_id'],
            'topografi_id' => $this->refs['topografi_id'],
            'peruntukan_id' => $this->refs['peruntukan_rumah_id'],
            'image' => UploadedFile::fake()->image('foto.jpg'),
        ], $overrides);
    };

    $this->similarPayload = fn (array $overrides = []) => array_merge([
        'latitude' => -2.5489,
        'longitude' => 118.0149,
        'district_id' => $this->district->id,
        'market_basis' => 'sale',
        'jenis_objek' => 'rumah_tinggal',
        'peruntukan' => 'rumah_tinggal',
        'luas_tanah' => 120,
        'luas_bangunan' => 60,
        'dokumen_tanah' => 'sertifikat_hak_milik',
        'lebar_jalan' => 6,
        'posisi_tanah' => 'interior_lot',
        'kondisi_tanah' => 'matang',
        'limit' => 10,
        'range_km' => 10,
    ], $overrides);
});

it('returns paginated pembanding list', function () {
    ($this->makePembanding)(['alamat_data' => 'Jl. A']);
    ($this->makePembanding)(['alamat_data' => 'Jl. B', 'latitude' => -2.5495, 'longitude' => 118.0155]);

    $response = $this->getJson('/api/v1/pembandings?limit=1');

    $response
        ->assertOk()
        ->assertJsonPath('status', 'success');

    $perPage = $response->json('meta.per_page') ?? $response->json('data.per_page');
    expect($perPage)->toBe(1);

    $items = $response->json('data.data') ?? $response->json('data');
    expect($items)->toHaveCount(1);
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

    $items = $response->json('data.data') ?? $response->json('data');
    expect($items)->toHaveCount(1)
        ->and($items[0]['id'])->toBe($rumah->id);
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
        ->assertJsonPath('data.0.is_fallback', true)
        ->assertJsonPath('data.0.retrieval_stage', 'cross_peruntukan_fallback');

    expect($response->json('data.0.fallback_reason'))->toBeString()->not->toBeEmpty();
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

it('accepts active dictionary slugs in similar payload', function () {
    $response = $this->postJson('/api/v1/pembandings/similar', [
        'latitude' => -2.5489,
        'longitude' => 118.0149,
        'district_id' => $this->district->id,
        'peruntukan' => 'rumah_tinggal',
        'dokumen_tanah' => 'sertifikat_hak_milik',
        'posisi_tanah' => 'interior_lot',
        'kondisi_tanah' => 'matang',
        'limit' => 10,
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonMissingPath('errors');
});

it('returns 422 when similar payload uses inactive dictionary slug', function () {
    Peruntukan::query()->whereKey($this->refs['peruntukan_rumah_id'])->update([
        'is_active' => false,
    ]);

    $response = $this->postJson('/api/v1/pembandings/similar', [
        'latitude' => -2.5489,
        'longitude' => 118.0149,
        'district_id' => $this->district->id,
        'peruntukan' => 'rumah_tinggal',
        'dokumen_tanah' => 'sertifikat_hak_milik',
        'posisi_tanah' => 'interior_lot',
        'kondisi_tanah' => 'matang',
        'limit' => 10,
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'peruntukan',
        ]);
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

it('can store new pembanding', function () {
    $response = $this->postJson('/api/v1/pembandings', ($this->validPembandingPayload)());

    $response
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.alamat_data', 'Jl. Test Create');
});

it('rejects an exact duplicate pembanding with conflict response', function () {
    $image = UploadedFile::fake()->image('foto.jpg');
    $imageContents = file_get_contents($image->getRealPath());
    $payload = ($this->validPembandingPayload)(['image' => $image]);

    $first = $this->post('/api/v1/pembandings', $payload);
    $first->assertOk();

    $duplicatePayload = ($this->validPembandingPayload)([
        'image' => UploadedFile::fake()->createWithContent('foto.jpg', $imageContents),
    ]);

    $this->post('/api/v1/pembandings', $duplicatePayload)
        ->assertStatus(409)
        ->assertJsonPath('status', 'error')
        ->assertJsonPath('duplicate.status', 'active')
        ->assertJsonPath('duplicate.id', $first->json('data.id'));
});

it('accepts the same coordinates when another business field differs', function () {
    $image = UploadedFile::fake()->image('foto.jpg');
    $imageContents = file_get_contents($image->getRealPath());

    $this->post('/api/v1/pembandings', ($this->validPembandingPayload)([
        'image' => $image,
    ]))->assertOk();

    $this->post('/api/v1/pembandings', ($this->validPembandingPayload)([
        'harga' => 125000000,
        'image' => UploadedFile::fake()->createWithContent('foto.jpg', $imageContents),
    ]))
        ->assertOk()
        ->assertJsonPath('data.harga', 125000000);
});

it('rejects an update that would duplicate another record', function () {
    $image = UploadedFile::fake()->image('foto.jpg');
    $imageContents = file_get_contents($image->getRealPath());

    $first = $this->post('/api/v1/pembandings', ($this->validPembandingPayload)([
        'image' => $image,
    ]));
    $second = $this->post('/api/v1/pembandings', ($this->validPembandingPayload)([
        'harga' => 125000000,
        'image' => UploadedFile::fake()->createWithContent('foto.jpg', $imageContents),
    ]));

    $first->assertOk();
    $second->assertOk();

    $this->putJson(
        '/api/v1/pembandings/'.$second->json('data.id'),
        ($this->validPembandingPayload)(['image' => null])
    )
        ->assertStatus(409)
        ->assertJsonPath('duplicate.id', $first->json('data.id'));
});

it('rejects recreation of an exact soft deleted record', function () {
    $image = UploadedFile::fake()->image('foto.jpg');
    $imageContents = file_get_contents($image->getRealPath());
    $first = $this->post('/api/v1/pembandings', ($this->validPembandingPayload)([
        'image' => $image,
    ]));

    $first->assertOk();
    Pembanding::query()->findOrFail($first->json('data.id'))->delete();

    $this->post('/api/v1/pembandings', ($this->validPembandingPayload)([
        'image' => UploadedFile::fake()->createWithContent('foto.jpg', $imageContents),
    ]))
        ->assertStatus(409)
        ->assertJsonPath('duplicate.status', 'deleted')
        ->assertJsonPath('duplicate.url', null);
});

it('validates sewa period through API create', function () {
    $sewaId = JenisListing::query()->create([
        'slug' => 'sewa',
        'name' => 'Sewa',
    ])->id;

    $this->postJson('/api/v1/pembandings', ($this->validPembandingPayload)([
        'jenis_listing_id' => $sewaId,
        'jangka_waktu_sewa' => null,
        'satuan_waktu_sewa' => null,
    ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['jangka_waktu_sewa', 'satuan_waktu_sewa']);

    $this->postJson('/api/v1/pembandings', ($this->validPembandingPayload)([
        'jenis_listing_id' => $sewaId,
        'alamat_data' => 'Jl. Sewa Bulanan',
        'jangka_waktu_sewa' => 3,
        'satuan_waktu_sewa' => 'Bulan',
    ]))
        ->assertOk()
        ->assertJsonPath('data.jangka_waktu_sewa', 3)
        ->assertJsonPath('data.satuan_waktu_sewa', 'Bulan');

    $this->postJson('/api/v1/pembandings', ($this->validPembandingPayload)([
        'jenis_listing_id' => $sewaId,
        'alamat_data' => 'Jl. Sewa Tahunan',
        'jangka_waktu_sewa' => 1,
        'satuan_waktu_sewa' => 'Tahun',
    ]))
        ->assertOk()
        ->assertJsonPath('data.satuan_waktu_sewa', 'Tahun');
});

it('clears rent period for non sewa API create', function () {
    $response = $this->postJson('/api/v1/pembandings', ($this->validPembandingPayload)([
        'jangka_waktu_sewa' => 12,
        'satuan_waktu_sewa' => 'Bulan',
    ]));

    $response
        ->assertOk()
        ->assertJsonPath('data.jangka_waktu_sewa', null)
        ->assertJsonPath('data.satuan_waktu_sewa', null);
});

it('can update existing pembanding', function () {
    $record = ($this->makePembanding)();

    $response = $this->putJson("/api/v1/pembandings/{$record->id}", array_merge($record->toArray(), [
        'alamat_data' => 'Jl. Updated',
        'harga' => 200000000,
    ]));

    $response
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.alamat_data', 'Jl. Updated')
        ->assertJsonPath('data.harga', 200000000);
});

it('can update image through multipart workaround without deleting old image when absent', function () {
    $record = ($this->makePembanding)([
        'image' => 'foto_pembanding/old.jpg',
    ]);

    $this->putJson("/api/v1/pembandings/{$record->id}", array_merge($record->toArray(), [
        'alamat_data' => 'Jl. Updated Without Image',
    ]))
        ->assertOk()
        ->assertJsonPath('data.alamat_data', 'Jl. Updated Without Image');

    expect($record->refresh()->image)->toBe('foto_pembanding/old.jpg');

    $response = $this->post("/api/v1/pembandings/{$record->id}", array_merge(
        ($this->validPembandingPayload)([
            '_method' => 'PUT',
            'alamat_data' => 'Jl. Updated With Image',
            'image' => UploadedFile::fake()->image('new.jpg'),
        ])
    ));

    $response
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.alamat_data', 'Jl. Updated With Image');

    expect($record->refresh()->image)->toStartWith('foto_pembanding/');
    expect($record->image)->not->toBe('foto_pembanding/old.jpg');
});

it('returns 403 JSON when user lacks create permission', function () {
    $this->user->revokePermissionTo('create_data::pembanding');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->postJson('/api/v1/pembandings', ($this->validPembandingPayload)())
        ->assertForbidden()
        ->assertJsonPath('status', 'error');
});

it('cannot destroy pembanding without permission', function () {
    $record = ($this->makePembanding)();

    $this->deleteJson("/api/v1/pembandings/{$record->id}")
        ->assertStatus(403);
});

it('can destroy pembanding with permission', function () {
    $record = ($this->makePembanding)();

    // Assign permission to user (bypassing guard check)
    $permission = Permission::firstOrCreate(['name' => 'delete_data::pembanding', 'guard_name' => 'web']);
    DB::table('model_has_permissions')->insert([
        'permission_id' => $permission->id,
        'model_type' => get_class($this->user),
        'model_id' => $this->user->id,
    ]);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $this->deleteJson("/api/v1/pembandings/{$record->id}")
        ->assertOk()
        ->assertJsonPath('status', 'success');

    $this->assertSoftDeleted('data_pembanding', ['id' => $record->id]);
});

it('can fetch history of pembanding', function () {
    $record = ($this->makePembanding)();

    $record->update(['alamat_data' => 'Jl. History Updated']);

    $response = $this->getJson("/api/v1/pembandings/{$record->id}/history");

    $response
        ->assertOk()
        ->assertJsonPath('status', 'success');

    expect($response->json('data'))->toBeArray();
});

it('can submit delete request for pembanding', function () {
    $record = ($this->makePembanding)();

    $response = $this->postJson("/api/v1/pembandings/{$record->id}/delete-request", [
        'reason' => 'Data salah input',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('status', 'success');

    $this->assertDatabaseHas('pembanding_delete_requests', [
        'pembanding_id' => $record->id,
        'requested_by_id' => $this->user->id,
        'reason' => 'Data salah input',
        'status' => 'pending',
    ]);
});

it('cannot submit duplicate pending delete request', function () {
    $record = ($this->makePembanding)();

    $this->postJson("/api/v1/pembandings/{$record->id}/delete-request", [
        'reason' => 'Alasan pertama',
    ])->assertOk();

    $response = $this->postJson("/api/v1/pembandings/{$record->id}/delete-request", [
        'reason' => 'Alasan kedua',
    ]);

    $response->assertStatus(422);
});

it('returns additive scoring metadata when v2 is active', function () {
    config()->set('pembanding_scoring.mode', 'v2');

    ($this->makePembanding)([
        'alamat_data' => 'Kandidat V2',
        'latitude' => -2.5491,
        'longitude' => 118.0151,
    ]);

    $response = $this->postJson('/api/v1/pembandings/similar', [
        'latitude' => -2.5489,
        'longitude' => 118.0149,
        'district_id' => $this->district->id,
        'market_basis' => 'sale',
        'jenis_objek' => 'tanah',
        'peruntukan' => 'rumah_tinggal',
        'luas_tanah' => 120,
        'luas_bangunan' => 60,
        'dokumen_tanah' => 'sertifikat_hak_milik',
        'lebar_jalan' => 6,
        'posisi_tanah' => 'interior_lot',
        'kondisi_tanah' => 'matang',
        'limit' => 10,
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.0.method_version', 'heuristic-v2.0')
        ->assertJsonPath('data.0.score', $response->json('data.0.similarity_score'))
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'score',
                    'similarity_score',
                    'reference_coverage',
                    'score_coverage',
                    'scoring_status',
                    'method_version',
                    'similarity_rank',
                    'eligibility_tier',
                    'evidence_quality',
                    'evidence_tier',
                    'component_scores',
                    'warnings',
                    'retrieval_stage',
                    'report_readiness',
                    'report_completeness',
                    'report_missing_fields',
                    'record_version',
                ],
            ],
        ]);
});

it('scores a larger candidate pool before applying the result limit', function () {
    config()->set('pembanding_scoring.mode', 'v2');
    config()->set('pembanding_scoring.candidate_pool', [
        'minimum' => 2,
        'multiplier' => 2,
        'maximum' => 10,
    ]);

    $nearestButPoor = ($this->makePembanding)([
        'alamat_data' => 'Terdekat tetapi buruk',
        'latitude' => -2.5490,
        'longitude' => 118.0149,
        'luas_tanah' => 10000,
        'luas_bangunan' => 1000,
        'lebar_jalan' => 25,
    ]);
    $fartherButSimilar = ($this->makePembanding)([
        'alamat_data' => 'Lebih jauh tetapi mirip',
        'latitude' => -2.5500,
        'longitude' => 118.0149,
        'luas_tanah' => 120,
        'luas_bangunan' => 60,
        'lebar_jalan' => 6,
    ]);

    $response = $this->postJson('/api/v1/pembandings/similar', [
        'latitude' => -2.5489,
        'longitude' => 118.0149,
        'district_id' => $this->district->id,
        'market_basis' => 'sale',
        'jenis_objek' => 'tanah',
        'peruntukan' => 'rumah_tinggal',
        'luas_tanah' => 120,
        'luas_bangunan' => 60,
        'dokumen_tanah' => 'sertifikat_hak_milik',
        'lebar_jalan' => 6,
        'posisi_tanah' => 'interior_lot',
        'kondisi_tanah' => 'matang',
        'limit' => 1,
    ]);

    $response->assertOk()->assertJsonCount(1, 'data');

    expect($response->json('data.0.id'))->toBe($fartherButSimilar->id)
        ->and($response->json('data.0.id'))->not->toBe($nearestButPoor->id);
});

it('uses the candidate id as the final deterministic tie breaker', function () {
    config()->set('pembanding_scoring.mode', 'v2');

    $first = ($this->makePembanding)([
        'alamat_data' => 'Tie Candidate A',
        'latitude' => -2.5491,
        'longitude' => 118.0151,
    ]);
    $second = ($this->makePembanding)([
        'alamat_data' => 'Tie Candidate B',
        'latitude' => -2.5491,
        'longitude' => 118.0151,
    ]);

    $response = $this->postJson('/api/v1/pembandings/similar', [
        'latitude' => -2.5489,
        'longitude' => 118.0149,
        'district_id' => $this->district->id,
        'market_basis' => 'sale',
        'jenis_objek' => 'tanah',
        'peruntukan' => 'rumah_tinggal',
        'luas_tanah' => 120,
        'luas_bangunan' => 60,
        'dokumen_tanah' => 'sertifikat_hak_milik',
        'lebar_jalan' => 6,
        'posisi_tanah' => 'interior_lot',
        'kondisi_tanah' => 'matang',
        'limit' => 2,
    ]);

    $response->assertOk()->assertJsonCount(2, 'data');

    expect($response->json('data.0.id'))->toBe($first->id)
        ->and($response->json('data.1.id'))->toBe($second->id);
});

it('does not let rent candidates fill a sale candidate pool', function () {
    config()->set('pembanding_scoring.mode', 'v2');

    $rentListingId = JenisListing::query()->create([
        'slug' => 'sewa',
        'name' => 'Sewa',
    ])->id;
    $rentCandidate = ($this->makePembanding)([
        'alamat_data' => 'Kandidat Sewa',
        'jenis_listing_id' => $rentListingId,
        'latitude' => -2.5490,
        'longitude' => 118.0149,
    ]);
    $saleCandidate = ($this->makePembanding)([
        'alamat_data' => 'Kandidat Jual',
        'latitude' => -2.5500,
        'longitude' => 118.0149,
    ]);

    $response = $this->postJson('/api/v1/pembandings/similar', [
        'latitude' => -2.5489,
        'longitude' => 118.0149,
        'district_id' => $this->district->id,
        'market_basis' => 'sale',
        'jenis_objek' => 'tanah',
        'peruntukan' => 'rumah_tinggal',
        'limit' => 10,
    ]);

    $response->assertOk();
    $ids = collect($response->json('data'))->pluck('id');

    expect($ids)->toContain($saleCandidate->id)
        ->and($ids)->not->toContain($rentCandidate->id);
});

it('accepts market_basis parameter and falls back to infer from listing when absent', function () {
    $saleCandidate = ($this->makePembanding)([
        'alamat_data' => 'Kandidat Jual',
        'latitude' => -2.5500,
        'longitude' => 118.0149,
    ]);

    $response = $this->postJson('/api/v1/pembandings/similar', [
        'latitude' => -2.5489,
        'longitude' => 118.0149,
        'district_id' => $this->district->id,
        'peruntukan' => 'rumah_tinggal',
        'limit' => 10,
    ])->assertOk();

    expect(collect($response->json('data'))->pluck('id'))->toContain($saleCandidate->id);
});

it('marks insufficient v2 results as not rankable and returns a warning', function () {
    config()->set('pembanding_scoring.mode', 'v2');
    ($this->makePembanding)();

    $response = $this->postJson('/api/v1/pembandings/similar', [
        'latitude' => -2.5489,
        'longitude' => 118.0149,
        'district_id' => $this->district->id,
        'market_basis' => 'sale',
        'peruntukan' => 'rumah_tinggal',
        'limit' => 1,
    ])->assertOk();

    $response
        ->assertJsonPath('data.0.scoring_status', 'insufficient_input')
        ->assertJsonPath('data.0.rankable', false);

    expect($response->json('data.0.warnings'))->not->toBeEmpty();
});

it('does not use candidates dated after the requested reference date', function () {
    config()->set('pembanding_scoring.mode', 'v2');
    $past = ($this->makePembanding)([
        'alamat_data' => 'Past evidence',
        'tanggal_data' => '2024-12-31',
    ]);
    $future = ($this->makePembanding)([
        'alamat_data' => 'Future evidence',
        'tanggal_data' => '2025-01-02',
    ]);

    $response = $this->postJson('/api/v1/pembandings/similar', [
        'latitude' => -2.5489,
        'longitude' => 118.0149,
        'district_id' => $this->district->id,
        'market_basis' => 'sale',
        'reference_date' => '2025-01-01',
        'jenis_objek' => 'tanah',
        'peruntukan' => 'rumah_tinggal',
        'luas_tanah' => 120,
        'limit' => 10,
    ])->assertOk();

    $ids = collect($response->json('data'))->pluck('id');

    expect($ids)->toContain($past->id)
        ->and($ids)->not->toContain($future->id);
});

it('expands the v2 candidate pool from district to regency and radius', function () {
    config()->set('pembanding_scoring.mode', 'v2');
    config()->set('pembanding_scoring.candidate_pool.minimum', 3);
    config()->set('pembanding_scoring.candidate_pool.maximum', 3);

    $otherDistrict = District::query()->create([
        'id' => '710102',
        'regency_id' => $this->regency->id,
        'name' => 'Kecamatan Tetangga',
    ]);
    $otherRegency = Regency::query()->create([
        'id' => '7102',
        'province_id' => $this->province->id,
        'name' => 'Kabupaten Tetangga',
    ]);
    $radiusDistrict = District::query()->create([
        'id' => '710201',
        'regency_id' => $otherRegency->id,
        'name' => 'Kecamatan Lintas Batas',
    ]);

    ($this->makePembanding)(['alamat_data' => 'District', 'latitude' => -2.5490]);
    ($this->makePembanding)([
        'alamat_data' => 'Regency',
        'district_id' => $otherDistrict->id,
        'latitude' => -2.5491,
    ]);
    ($this->makePembanding)([
        'alamat_data' => 'Radius',
        'regency_id' => $otherRegency->id,
        'district_id' => $radiusDistrict->id,
        'latitude' => -2.5492,
    ]);

    $response = $this->postJson('/api/v1/pembandings/similar', [
        'latitude' => -2.5489,
        'longitude' => 118.0149,
        'district_id' => $this->district->id,
        'market_basis' => 'sale',
        'jenis_objek' => 'tanah',
        'peruntukan' => 'rumah_tinggal',
        'luas_tanah' => 120,
        'limit' => 3,
    ])->assertOk();

    expect(collect($response->json('data'))->pluck('retrieval_stage')->sort()->values()->all())
        ->toBe(['district', 'radius', 'regency']);
});

it('uses land area rather than total area for gudang land substitutions', function () {
    config()->set('pembanding_scoring.mode', 'v2');

    Peruntukan::query()->create(['slug' => 'gudang', 'name' => 'Gudang']);
    $candidate = ($this->makePembanding)([
        'alamat_data' => 'Tanah substitusi gudang',
        'peruntukan_id' => $this->refs['peruntukan_tanah_id'],
        'luas_tanah' => 1000,
        'luas_bangunan' => 800,
    ]);

    $response = $this->postJson('/api/v1/pembandings/similar', [
        'latitude' => -2.5489,
        'longitude' => 118.0149,
        'district_id' => $this->district->id,
        'market_basis' => 'sale',
        'peruntukan' => 'gudang',
        'luas_tanah' => 1000,
        'limit' => 10,
    ])->assertOk();

    expect(collect($response->json('data'))->pluck('id'))->toContain($candidate->id);
});

it('keeps report readiness separate from similarity ranking', function () {
    config()->set('pembanding_scoring.mode', 'v2');

    $incomplete = ($this->makePembanding)([
        'alamat_data' => 'Kandidat laporan incomplete',
        'harga' => null,
        'latitude' => -2.5490,
        'longitude' => 118.0149,
    ]);
    $ready = ($this->makePembanding)([
        'alamat_data' => 'Kandidat laporan ready',
        'harga' => 500000000,
        'latitude' => -2.5490,
        'longitude' => 118.0149,
    ]);

    $response = $this->postJson('/api/v1/pembandings/similar', [
        'latitude' => -2.5489,
        'longitude' => 118.0149,
        'district_id' => $this->district->id,
        'market_basis' => 'sale',
        'jenis_objek' => 'tanah',
        'peruntukan' => 'rumah_tinggal',
        'luas_tanah' => 120,
        'dokumen_tanah' => 'sertifikat_hak_milik',
        'lebar_jalan' => 6,
        'posisi_tanah' => 'interior_lot',
        'kondisi_tanah' => 'matang',
        'limit' => 10,
    ])->assertOk();

    $items = collect($response->json('data'))->keyBy('id');
    $incompleteResult = $items->get($incomplete->id);
    $readyResult = $items->get($ready->id);

    expect($incompleteResult['similarity_score'])->toBe($readyResult['similarity_score'])
        ->and($incompleteResult['component_scores'])->toBe($readyResult['component_scores'])
        ->and($incompleteResult['report_readiness'])->toBe('incomplete')
        ->and($incompleteResult['report_missing_fields'])->toContain('harga')
        ->and($readyResult['report_readiness'])->toBe('ready')
        ->and($incompleteResult['rank'])->toBeLessThan($readyResult['rank']);
});

it('ranks matching property specifications above a changed specification', function (string $field, mixed $value, float $expectedScore) {
    $houseId = JenisObjek::query()->create(['slug' => 'rumah_tinggal', 'name' => 'Rumah Tinggal'])->id;
    $overrides = [$field => $value];
    if ($field === 'dokumen_tanah_id') {
        $overrides[$field] = DokumenTanah::query()->create([
            'slug' => 'sertifikat_hak_guna_bangunan',
            'name' => 'Sertifikat Hak Guna Bangunan',
        ])->id;
    }

    // Create the weaker candidate first so an insertion-order sort cannot pass.
    $different = ($this->makePembanding)(array_merge(['jenis_objek_id' => $houseId], $overrides));
    $exact = ($this->makePembanding)(['jenis_objek_id' => $houseId]);

    $response = $this->postJson('/api/v1/pembandings/similar', ($this->similarPayload)())
        ->assertOk()->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $exact->id)
        ->assertJsonPath('data.1.id', $different->id)
        ->assertJsonPath('data.0.rankable', true)
        ->assertJsonPath('data.0.scoring_status', 'scored');

    expect((float) $response->json('data.0.similarity_score'))->toBe(100.0)
        ->and((float) $response->json('data.1.similarity_score'))->toBe($expectedScore);
})->with([
    'land area doubles' => ['luas_tanah', 240, 95.0],
    'building area doubles' => ['luas_bangunan', 120, 96.0],
    'SHM changes to HGB' => ['dokumen_tanah_id', null, 97.2],
]);

it('returns consistent candidate scores through the id and JSON endpoints without storing the JSON reference', function () {
    $houseId = JenisObjek::query()->create(['slug' => 'rumah_tinggal', 'name' => 'Rumah Tinggal'])->id;
    $reference = ($this->makePembanding)(['jenis_objek_id' => $houseId]);
    ($this->makePembanding)(['jenis_objek_id' => $houseId, 'latitude' => -2.55]);
    ($this->makePembanding)(['jenis_objek_id' => $houseId, 'luas_bangunan' => 120]);
    $count = Pembanding::query()->count();

    $byId = $this->getJson("/api/v1/pembandings/{$reference->id}/similar?limit=10&range_km=10")
        ->assertOk()->assertJsonCount(2, 'data');
    $byPayload = $this->postJson('/api/v1/pembandings/similar', ($this->similarPayload)())
        ->assertOk()->assertJsonCount(3, 'data');

    $project = fn (array $item) => Arr::only($item, [
        'id', 'similarity_score', 'component_scores', 'distance', 'rankable',
        'scoring_status', 'reference_coverage', 'score_coverage', 'eligibility_tier',
    ]);
    expect(collect($byId->json('data'))->pluck('id'))->not->toContain($reference->id)
        ->and(collect($byId->json('data'))->map($project)->all())->toBe(
            collect($byPayload->json('data'))->reject(fn ($item) => $item['id'] === $reference->id)
                ->map($project)->values()->all(),
        )
        ->and(Pembanding::query()->count())->toBe($count);
});

it('protects both similarity endpoints when the user lacks browse permission', function () {
    $reference = ($this->makePembanding)();
    $this->user->revokePermissionTo('view_any_data::pembanding');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->getJson("/api/v1/pembandings/{$reference->id}/similar")->assertForbidden();
    $this->postJson('/api/v1/pembandings/similar', ($this->similarPayload)())->assertForbidden();
});

it('returns no candidates when the only stored candidate is soft deleted', function () {
    $houseId = JenisObjek::query()->create(['slug' => 'rumah_tinggal', 'name' => 'Rumah Tinggal'])->id;
    $candidate = ($this->makePembanding)(['jenis_objek_id' => $houseId]);
    $candidate->delete();

    $this->postJson('/api/v1/pembandings/similar', ($this->similarPayload)())
        ->assertOk()->assertJsonCount(0, 'data');
    $this->getJson("/api/v1/pembandings/{$candidate->id}/similar")->assertNotFound();
});
