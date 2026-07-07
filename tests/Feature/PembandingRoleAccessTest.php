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
use Database\Seeders\PembandingAccessRoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(PembandingAccessRoleSeeder::class);

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
        'jenis_listing_id' => JenisListing::query()->create(['slug' => 'transaksi', 'name' => 'Transaksi'])->id,
        'jenis_objek_id' => JenisObjek::query()->create(['slug' => 'tanah', 'name' => 'Tanah'])->id,
        'status_pemberi_informasi_id' => StatusPemberiInformasi::query()->create(['slug' => 'agen', 'name' => 'Agen'])->id,
        'bentuk_tanah_id' => BentukTanah::query()->create(['slug' => 'persegi', 'name' => 'Persegi'])->id,
        'dokumen_tanah_id' => DokumenTanah::query()->create(['slug' => 'shm', 'name' => 'SHM'])->id,
        'posisi_tanah_id' => PosisiTanah::query()->create(['slug' => 'interior', 'name' => 'Interior'])->id,
        'kondisi_tanah_id' => KondisiTanah::query()->create(['slug' => 'matang', 'name' => 'Matang'])->id,
        'topografi_id' => Topografi::query()->create(['slug' => 'datar', 'name' => 'Datar'])->id,
        'peruntukan_id' => Peruntukan::query()->create(['slug' => 'rumah', 'name' => 'Rumah'])->id,
    ];
});

function roleUser(string $role): User
{
    $user = User::factory()->create(['deactivated_at' => null]);
    $user->assignRole($role);
    Sanctum::actingAs($user);

    return $user;
}

function pembandingPayload(array $overrides = []): array
{
    return array_merge([
        'nama_pemberi_informasi' => 'Sumber Data',
        'nomer_telepon_pemberi_informasi' => '08123456789',
        'alamat_data' => 'Jl. Role Test',
        'latitude' => -2.5,
        'longitude' => 118.0,
        'luas_tanah' => 100,
        'luas_bangunan' => null,
        'tahun_bangun' => null,
        'lebar_depan' => 10,
        'lebar_jalan' => 5,
        'harga' => 100000000,
        'tanggal_data' => now()->toDateString(),
        'province_id' => test()->province->id,
        'regency_id' => test()->regency->id,
        'district_id' => test()->district->id,
        'village_id' => test()->village->id,
        'jenis_listing_id' => test()->refs['jenis_listing_id'],
        'jenis_objek_id' => test()->refs['jenis_objek_id'],
        'status_pemberi_informasi_id' => test()->refs['status_pemberi_informasi_id'],
        'bentuk_tanah_id' => test()->refs['bentuk_tanah_id'],
        'dokumen_tanah_id' => test()->refs['dokumen_tanah_id'],
        'posisi_tanah_id' => test()->refs['posisi_tanah_id'],
        'kondisi_tanah_id' => test()->refs['kondisi_tanah_id'],
        'topografi_id' => test()->refs['topografi_id'],
        'peruntukan_id' => test()->refs['peruntukan_id'],
    ], $overrides);
}

function createPembandingFor(User $user, array $overrides = []): Pembanding
{
    return Pembanding::query()->create(pembandingPayload(array_merge([
        'created_by' => $user->id,
    ], $overrides)));
}

it('seeds pimpinan and data contributor roles with scoped pembanding permissions', function () {
    expect(Role::findByName('pimpinan')->permissions->pluck('name')->all())
        ->toContain('view_map', 'view_any_data::pembanding', 'create_data::pembanding', 'update_data::pembanding')
        ->not->toContain('delete_data::pembanding', 'export_data::pembanding');

    expect(Role::findByName('data_contributor')->permissions->pluck('name')->all())
        ->toContain('view_map', 'view_any_data::pembanding', 'create_data::pembanding', 'update_own_data::pembanding')
        ->not->toContain('update_data::pembanding', 'delete_data::pembanding', 'export_data::pembanding');
});

it('allows pimpinan to create and update pembanding but not delete it', function () {
    $user = roleUser('pimpinan');

    $create = $this->postJson('/api/v1/pembandings', pembandingPayload([
        'alamat_data' => 'Jl. Pimpinan Create',
        'image' => UploadedFile::fake()->image('foto.jpg'),
    ]));

    $create->assertOk()->assertJsonPath('data.alamat_data', 'Jl. Pimpinan Create');

    $record = Pembanding::query()->findOrFail($create->json('data.id'));

    $this->putJson("/api/v1/pembandings/{$record->id}", pembandingPayload([
        'alamat_data' => 'Jl. Pimpinan Update',
    ]))
        ->assertOk()
        ->assertJsonPath('data.alamat_data', 'Jl. Pimpinan Update');

    $this->deleteJson("/api/v1/pembandings/{$record->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('data_pembanding', [
        'id' => $record->id,
        'deleted_at' => null,
        'created_by' => $user->id,
    ]);
});

it('allows data contributor to create and update only their own pembanding', function () {
    $user = roleUser('data_contributor');
    $otherUser = User::factory()->create();

    $create = $this->postJson('/api/v1/pembandings', pembandingPayload([
        'alamat_data' => 'Jl. Contributor Create',
        'image' => UploadedFile::fake()->image('foto.jpg'),
    ]));

    $create->assertOk()->assertJsonPath('data.alamat_data', 'Jl. Contributor Create');

    $ownRecord = Pembanding::query()->findOrFail($create->json('data.id'));
    $otherRecord = createPembandingFor($otherUser, ['alamat_data' => 'Jl. Orang Lain']);

    $this->putJson("/api/v1/pembandings/{$ownRecord->id}", pembandingPayload([
        'alamat_data' => 'Jl. Contributor Own Update',
    ]))
        ->assertOk()
        ->assertJsonPath('data.alamat_data', 'Jl. Contributor Own Update');

    $this->putJson("/api/v1/pembandings/{$otherRecord->id}", pembandingPayload([
        'alamat_data' => 'Jl. Tidak Boleh',
    ]))
        ->assertForbidden();

    $this->assertDatabaseHas('data_pembanding', [
        'id' => $otherRecord->id,
        'alamat_data' => 'Jl. Orang Lain',
    ]);
});

it('returns an actionable web form error for an exact duplicate', function () {
    Storage::fake('public');
    $user = roleUser('data_contributor');
    $this->actingAs($user);
    $image = UploadedFile::fake()->image('foto.jpg');
    $imageContents = file_get_contents($image->getRealPath());

    $this->post('/app/pembanding', pembandingPayload([
        'image' => $image,
    ]))->assertRedirect();

    $record = Pembanding::query()->sole();

    $this->from('/app/pembanding/create')
        ->post('/app/pembanding', pembandingPayload([
            'image' => UploadedFile::fake()->createWithContent('foto.jpg', $imageContents),
        ]))
        ->assertRedirect('/app/pembanding/create')
        ->assertSessionHasErrors('duplicate')
        ->assertSessionHas('duplicate.id', $record->id)
        ->assertSessionHas('duplicate.url', url("/app/pembanding/{$record->id}"));
});

it('prevents data contributor from deleting any pembanding', function () {
    $user = roleUser('data_contributor');
    $ownRecord = createPembandingFor($user);
    $otherRecord = createPembandingFor(User::factory()->create());

    $this->deleteJson("/api/v1/pembandings/{$ownRecord->id}")
        ->assertForbidden();

    $this->deleteJson("/api/v1/pembandings/{$otherRecord->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('data_pembanding', ['id' => $ownRecord->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('data_pembanding', ['id' => $otherRecord->id, 'deleted_at' => null]);
});
