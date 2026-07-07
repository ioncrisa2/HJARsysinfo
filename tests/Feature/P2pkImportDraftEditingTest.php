<?php

use App\Models\BentukTanah;
use App\Models\District;
use App\Models\DokumenTanah;
use App\Models\JenisListing;
use App\Models\JenisObjek;
use App\Models\KondisiTanah;
use App\Models\P2pkImportBatch;
use App\Models\P2pkImportRow;
use App\Models\Peruntukan;
use App\Models\PosisiTanah;
use App\Models\Province;
use App\Models\Regency;
use App\Models\StatusPemberiInformasi;
use App\Models\Topografi;
use App\Models\User;
use App\Models\Village;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\PembandingAccessRoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
    $this->seed([MasterDataSeeder::class, PembandingAccessRoleSeeder::class]);

    Province::query()->create(['id' => '97', 'name' => 'Provinsi Draft']);
    Regency::query()->create(['id' => '9701', 'province_id' => '97', 'name' => 'Kabupaten Draft']);
    District::query()->create(['id' => '9701010', 'regency_id' => '9701', 'name' => 'Kecamatan Draft']);
    Village::query()->create(['id' => '9701010001', 'district_id' => '9701010', 'name' => 'Desa Draft']);
});

function p2pkDraftEditingUser(string $role = 'bulk_import'): User
{
    $user = User::factory()->create(['deactivated_at' => null]);
    $user->assignRole($role);

    return $user;
}

function p2pkDraftEditingBatch(User $owner, array $attributes = []): P2pkImportBatch
{
    $token = (string) Str::uuid();
    $path = "p2pk-imports/{$owner->id}/{$token}/source.xlsx";
    Storage::disk('local')->put($path, 'private workbook fixture');

    return P2pkImportBatch::query()->create(array_replace([
        'owner_id' => $owner->id,
        'original_filename' => 'draft.xlsx',
        'source_disk' => 'local',
        'source_path' => $path,
        'file_checksum' => hash('sha256', $token),
        'sheet_name' => 'Data_Pembanding',
        'status' => P2pkImportBatch::STATUS_DRAFT,
        'total_rows' => 0,
        'selected_rows' => 0,
        'ready_rows' => 0,
        'last_activity_at' => now(),
    ], $attributes));
}

function p2pkDraftEditingRow(P2pkImportBatch $batch, array $attributes = []): P2pkImportRow
{
    $number = $attributes['source_row_number'] ?? ($batch->rows()->count() + 2);

    $row = $batch->rows()->create(array_replace([
        'source_row_number' => $number,
        'source_fingerprint' => hash('sha256', "{$batch->id}:{$number}:".Str::uuid()),
        'status' => P2pkImportRow::STATUS_INCOMPLETE,
        'is_selected' => true,
        'raw_payload' => ['Nomor Laporan Penilaian' => "LP-{$number}"],
        'mapped_payload' => [
            'alamat_data' => "Jalan Draft {$number}",
            'province_id' => '97',
            'regency_id' => '9701',
            'district_id' => '9701010',
            'village_id' => '9701010001',
        ],
        'missing_fields' => [['field' => 'image', 'label' => 'Gambar']],
        'warnings' => [],
    ], $attributes));

    $batch->update([
        'total_rows' => $batch->rows()->count(),
        'selected_rows' => $batch->rows()->where('is_selected', true)->count(),
        'ready_rows' => $batch->rows()->where('status', P2pkImportRow::STATUS_READY)->count(),
    ]);

    return $row;
}

function p2pkCompleteTanahDraftPayload(array $overrides = []): array
{
    return array_replace([
        'jenis_listing_id' => JenisListing::query()->where('slug', 'penawaran')->value('id'),
        'jenis_objek_id' => JenisObjek::query()->where('slug', 'tanah')->value('id'),
        'nama_pemberi_informasi' => 'Pemilik Aset',
        'nomer_telepon_pemberi_informasi' => null,
        'status_pemberi_informasi_id' => StatusPemberiInformasi::query()->where('slug', 'pemilik_properti')->value('id'),
        'alamat_data' => 'Jalan Draft 2',
        'province_id' => '97',
        'regency_id' => '9701',
        'district_id' => '9701010',
        'village_id' => '9701010001',
        'latitude' => -6.2,
        'longitude' => 106.8,
        'luas_tanah' => 100,
        'luas_bangunan' => null,
        'tahun_bangun' => null,
        'lebar_depan' => 10,
        'lebar_jalan' => 6,
        'rasio_tapak' => null,
        'bentuk_tanah_id' => BentukTanah::query()->where('slug', 'persegi_panjang')->value('id'),
        'posisi_tanah_id' => PosisiTanah::query()->where('slug', 'interior_lot')->value('id'),
        'kondisi_tanah_id' => KondisiTanah::query()->where('slug', 'matang')->value('id'),
        'topografi_id' => Topografi::query()->where('slug', 'datar_dengan_jalan')->value('id'),
        'dokumen_tanah_id' => DokumenTanah::query()->where('slug', 'sertifikat_hak_milik')->value('id'),
        'peruntukan_id' => Peruntukan::query()->where('slug', 'tanah_kosong')->value('id'),
        'harga' => 100000000,
        'catatan' => null,
    ], $overrides);
}

it('allows only the owner or super admin to open and change a draft row', function () {
    $owner = p2pkDraftEditingUser();
    $other = p2pkDraftEditingUser();
    $superAdmin = p2pkDraftEditingUser('super_admin');
    $batch = p2pkDraftEditingBatch($owner);
    $row = p2pkDraftEditingRow($batch);
    $editUrl = "/app/pembanding-imports/{$batch->id}/rows/{$row->id}/edit";
    $updateUrl = "/app/pembanding-imports/{$batch->id}/rows/{$row->id}";

    $this->actingAs($owner)->get($editUrl)->assertOk();
    $this->actingAs($superAdmin)->get($editUrl)->assertOk();
    $this->actingAs($other)->get($editUrl)->assertForbidden();
    $this->actingAs($other)->put($updateUrl, ['nama_pemberi_informasi' => 'Tidak Berhak'])->assertForbidden();
    $this->actingAs($other)->patch("/app/pembanding-imports/{$batch->id}/selection", [
        'action' => 'clear_all',
    ])->assertForbidden();

    expect($row->fresh()->mapped_payload)->not->toHaveKey('nama_pemberi_informasi');
});

it('saves partial work as an incomplete draft without creating main data', function () {
    $owner = p2pkDraftEditingUser();
    $batch = p2pkDraftEditingBatch($owner);
    $row = p2pkDraftEditingRow($batch);

    $this->actingAs($owner)
        ->put("/app/pembanding-imports/{$batch->id}/rows/{$row->id}", [
            'nama_pemberi_informasi' => 'Budi Pemilik',
            'lebar_depan' => 10,
        ])
        ->assertRedirect();

    $row->refresh();
    expect($row->status)->toBe(P2pkImportRow::STATUS_INCOMPLETE)
        ->and($row->mapped_payload['nama_pemberi_informasi'])->toBe('Budi Pemilik')
        ->and($row->mapped_payload['lebar_depan'])->toBeNumeric()
        ->and(collect($row->missing_fields)->pluck('field'))->toContain('image');
    $this->assertDatabaseCount('data_pembanding', 0);
});

it('marks a completed tanah draft ready and serves its image only through the protected route', function () {
    $owner = p2pkDraftEditingUser();
    $batch = p2pkDraftEditingBatch($owner);
    $row = p2pkDraftEditingRow($batch);
    $payload = p2pkCompleteTanahDraftPayload([
        'image' => UploadedFile::fake()->image('aset.jpg', 800, 600),
    ]);

    $this->actingAs($owner)
        ->post("/app/pembanding-imports/{$batch->id}/rows/{$row->id}", [
            '_method' => 'PUT',
            ...$payload,
        ])
        ->assertRedirect();

    $row->refresh();
    $batch->refresh();
    expect($row->status)->toBe(P2pkImportRow::STATUS_READY)
        ->and($row->missing_fields)->toBeEmpty()
        ->and($row->staging_image_path)->not->toBeNull()
        ->and($batch->ready_rows)->toBe(1)
        ->and($batch->selected_rows)->toBe(1);
    Storage::disk('local')->assertExists($row->staging_image_path);
    Storage::disk('public')->assertMissing($row->staging_image_path);

    $imageUrl = "/app/pembanding-imports/{$batch->id}/rows/{$row->id}/image";
    $this->actingAs($owner)->get($imageUrl)->assertOk()->assertHeader('content-type', 'image/jpeg');
    $this->actingAs(p2pkDraftEditingUser())->get($imageUrl)->assertForbidden();
    $this->assertDatabaseCount('data_pembanding', 0);
});

it('deletes the previous private image when a draft image is replaced', function () {
    $owner = p2pkDraftEditingUser();
    $batch = p2pkDraftEditingBatch($owner);
    $row = p2pkDraftEditingRow($batch);
    $url = "/app/pembanding-imports/{$batch->id}/rows/{$row->id}";

    $this->actingAs($owner)->post($url, [
        '_method' => 'PUT',
        ...p2pkCompleteTanahDraftPayload([
            'image' => UploadedFile::fake()->image('lama.jpg'),
        ]),
    ])->assertRedirect();
    $oldPath = $row->fresh()->staging_image_path;
    Storage::disk('local')->assertExists($oldPath);

    $this->actingAs($owner)->post($url, [
        '_method' => 'PUT',
        ...p2pkCompleteTanahDraftPayload([
            'image' => UploadedFile::fake()->image('baru.png'),
        ]),
    ])->assertRedirect();
    $newPath = $row->fresh()->staging_image_path;

    expect($newPath)->not->toBe($oldPath);
    Storage::disk('local')->assertMissing($oldPath);
    Storage::disk('local')->assertExists($newPath);
    $this->assertDatabaseCount('data_pembanding', 0);
});

it('removes a private image and marks the selected row incomplete again', function () {
    $owner = p2pkDraftEditingUser();
    $batch = p2pkDraftEditingBatch($owner);
    $row = p2pkDraftEditingRow($batch);
    $url = "/app/pembanding-imports/{$batch->id}/rows/{$row->id}";

    $this->actingAs($owner)->post($url, [
        '_method' => 'PUT',
        ...p2pkCompleteTanahDraftPayload([
            'image' => UploadedFile::fake()->image('aset.jpg'),
        ]),
    ])->assertRedirect();

    $row->refresh();
    $oldPath = $row->staging_image_path;
    expect($row->status)->toBe(P2pkImportRow::STATUS_READY);
    Storage::disk('local')->assertExists($oldPath);

    $this->actingAs($owner)->put($url, ['remove_image' => true])->assertRedirect();

    $row->refresh();
    $batch->refresh();
    expect($row->staging_image_path)->toBeNull()
        ->and($row->status)->toBe(P2pkImportRow::STATUS_INCOMPLETE)
        ->and(collect($row->missing_fields)->pluck('field'))->toContain('image')
        ->and($batch->selected_rows)->toBe(1)
        ->and($batch->ready_rows)->toBe(0);
    Storage::disk('local')->assertMissing($oldPath);
    $this->assertDatabaseCount('data_pembanding', 0);
});

it('supports all selection modes while refusing to select duplicate rows', function () {
    $owner = p2pkDraftEditingUser();
    $batch = p2pkDraftEditingBatch($owner);
    $ready = p2pkDraftEditingRow($batch, [
        'status' => P2pkImportRow::STATUS_READY,
        'is_selected' => false,
        'missing_fields' => [],
    ]);
    $incomplete = p2pkDraftEditingRow($batch, ['is_selected' => false]);
    $confirmation = p2pkDraftEditingRow($batch, [
        'status' => P2pkImportRow::STATUS_NEEDS_CONFIRMATION,
        'is_selected' => false,
    ]);
    $duplicate = p2pkDraftEditingRow($batch, [
        'status' => P2pkImportRow::STATUS_DUPLICATE,
        'is_selected' => false,
        'duplicate_of_row_id' => $ready->id,
    ]);
    $url = "/app/pembanding-imports/{$batch->id}/selection";

    $this->actingAs($owner)->patch($url, [
        'action' => 'set_rows',
        'row_ids' => [$incomplete->id],
        'is_selected' => true,
    ])->assertRedirect();
    $batch->refresh();
    expect($incomplete->fresh()->is_selected)->toBeTrue()
        ->and($duplicate->fresh()->is_selected)->toBeFalse()
        ->and($batch->selected_rows)->toBe(1)
        ->and($batch->ready_rows)->toBe(0);

    $this->actingAs($owner)->patch($url, [
        'action' => 'set_rows',
        'row_ids' => [$duplicate->id],
        'is_selected' => true,
    ])->assertSessionHasErrors('selection');
    $batch->refresh();
    expect($duplicate->fresh()->is_selected)->toBeFalse()
        ->and($batch->selected_rows)->toBe(1);

    $this->actingAs($owner)->patch($url, ['action' => 'select_all'])->assertRedirect();
    $batch->refresh();
    expect($batch->selected_rows)->toBe(3)
        ->and($batch->ready_rows)->toBe(1)
        ->and($duplicate->fresh()->is_selected)->toBeFalse();

    $this->actingAs($owner)->patch($url, ['action' => 'clear_all'])->assertRedirect();
    $batch->refresh();
    expect($batch->selected_rows)->toBe(0)
        ->and($batch->ready_rows)->toBe(0);

    $this->actingAs($owner)->patch($url, ['action' => 'select_ready'])->assertRedirect();
    $batch->refresh();
    expect($ready->fresh()->is_selected)->toBeTrue()
        ->and($incomplete->fresh()->is_selected)->toBeFalse()
        ->and($confirmation->fresh()->is_selected)->toBeFalse()
        ->and($duplicate->fresh()->is_selected)->toBeFalse()
        ->and($batch->selected_rows)->toBe(1)
        ->and($batch->ready_rows)->toBe(1);
    $this->assertDatabaseCount('data_pembanding', 0);
});

it('bulk applies an allowed value only to selected nonduplicate rows and refreshes readiness counters', function () {
    $owner = p2pkDraftEditingUser();
    $batch = p2pkDraftEditingBatch($owner);
    $imagePath = "p2pk-imports/{$owner->id}/images/ready-target.jpg";
    Storage::disk('local')->put($imagePath, 'private image fixture');

    $almostReadyPayload = p2pkCompleteTanahDraftPayload();
    unset($almostReadyPayload['bentuk_tanah_id']);
    $almostReady = p2pkDraftEditingRow($batch, [
        'mapped_payload' => $almostReadyPayload,
        'missing_fields' => [['field' => 'bentuk_tanah_id', 'label' => 'Bentuk tanah']],
        'staging_image_disk' => 'local',
        'staging_image_path' => $imagePath,
    ]);
    $selectedIncomplete = p2pkDraftEditingRow($batch);
    $unselected = p2pkDraftEditingRow($batch, ['is_selected' => false]);
    $duplicate = p2pkDraftEditingRow($batch, [
        'status' => P2pkImportRow::STATUS_DUPLICATE,
        'is_selected' => true,
        'duplicate_of_row_id' => $almostReady->id,
    ]);
    $batch->update(['selected_rows' => 99, 'ready_rows' => 99]);
    $shapeId = BentukTanah::query()->where('slug', 'persegi_panjang')->value('id');

    $this->actingAs($owner)
        ->patch("/app/pembanding-imports/{$batch->id}/bulk-apply", [
            'field' => 'bentuk_tanah_id',
            'value' => $shapeId,
        ])
        ->assertRedirect();

    $almostReady->refresh();
    $selectedIncomplete->refresh();
    $unselected->refresh();
    $duplicate->refresh();
    $batch->refresh();
    expect($almostReady->mapped_payload['bentuk_tanah_id'])->toBe($shapeId)
        ->and($selectedIncomplete->mapped_payload['bentuk_tanah_id'])->toBe($shapeId)
        ->and($unselected->mapped_payload)->not->toHaveKey('bentuk_tanah_id')
        ->and($duplicate->mapped_payload)->not->toHaveKey('bentuk_tanah_id')
        ->and($almostReady->status)->toBe(P2pkImportRow::STATUS_READY)
        ->and($selectedIncomplete->status)->toBe(P2pkImportRow::STATUS_INCOMPLETE)
        ->and($batch->selected_rows)->toBe(3)
        ->and($batch->ready_rows)->toBe(1);
    $this->assertDatabaseCount('data_pembanding', 0);
});

it('rejects unsafe fields from bulk apply', function (string $field, mixed $value) {
    $owner = p2pkDraftEditingUser();
    $batch = p2pkDraftEditingBatch($owner);
    $row = p2pkDraftEditingRow($batch);
    $originalPayload = $row->mapped_payload;

    $this->actingAs($owner)
        ->patch("/app/pembanding-imports/{$batch->id}/bulk-apply", [
            'field' => $field,
            'value' => $value,
        ])
        ->assertSessionHasErrors('field');

    expect($row->fresh()->mapped_payload)->toBe($originalPayload);
    $this->assertDatabaseCount('data_pembanding', 0);
})->with([
    'harga' => ['harga', 1],
    'alamat' => ['alamat_data', 'Alamat yang dipaksakan'],
]);
