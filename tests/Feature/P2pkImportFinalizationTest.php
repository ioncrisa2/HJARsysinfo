<?php

use App\Actions\P2pk\ProcessP2pkImportRowAction;
use App\Actions\P2pk\RefreshP2pkImportBatchSummaryAction;
use App\Jobs\ProcessP2pkImportChunk;
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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
    $this->seed([MasterDataSeeder::class, PembandingAccessRoleSeeder::class]);

    Province::query()->create(['id' => '96', 'name' => 'Provinsi Finalisasi']);
    Regency::query()->create(['id' => '9601', 'province_id' => '96', 'name' => 'Kabupaten Finalisasi']);
    District::query()->create(['id' => '9601010', 'regency_id' => '9601', 'name' => 'Kecamatan Finalisasi']);
    Village::query()->create(['id' => '9601010001', 'district_id' => '9601010', 'name' => 'Desa Finalisasi']);
});

function p2pkFinalizationUser(string $role = 'bulk_import'): User
{
    $user = User::factory()->create(['deactivated_at' => null]);
    $user->assignRole($role);

    return $user;
}

function p2pkFinalizationBatch(User $owner, array $attributes = []): P2pkImportBatch
{
    $token = (string) Str::uuid();
    $sourcePath = "p2pk-imports/{$owner->id}/{$token}/source.xlsx";
    Storage::disk('local')->put($sourcePath, 'private workbook fixture');

    return P2pkImportBatch::query()->create(array_replace([
        'owner_id' => $owner->id,
        'original_filename' => 'finalisasi.xlsx',
        'source_disk' => 'local',
        'source_path' => $sourcePath,
        'file_checksum' => hash('sha256', $token),
        'sheet_name' => 'Data_Pembanding',
        'status' => P2pkImportBatch::STATUS_DRAFT,
        'total_rows' => 0,
        'selected_rows' => 0,
        'ready_rows' => 0,
        'imported_rows' => 0,
        'failed_rows' => 0,
        'last_activity_at' => now(),
    ], $attributes));
}

function p2pkFinalizationPayload(int $number, array $overrides = []): array
{
    return array_replace([
        'jenis_listing_id' => JenisListing::query()->where('slug', 'penawaran')->value('id'),
        'jenis_objek_id' => JenisObjek::query()->where('slug', 'tanah')->value('id'),
        'nama_pemberi_informasi' => "Pemilik Aset {$number}",
        'nomer_telepon_pemberi_informasi' => null,
        'status_pemberi_informasi_id' => StatusPemberiInformasi::query()->where('slug', 'pemilik_properti')->value('id'),
        'alamat_data' => "Jalan Finalisasi {$number}",
        'province_id' => '96',
        'regency_id' => '9601',
        'district_id' => '9601010',
        'village_id' => '9601010001',
        'latitude' => -6.2 - ($number / 100000),
        'longitude' => 106.8 + ($number / 100000),
        'luas_tanah' => 100 + $number,
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
        'harga' => 100000000 + $number,
        'catatan' => null,
    ], $overrides);
}

function p2pkFinalizationRow(P2pkImportBatch $batch, array $attributes = []): P2pkImportRow
{
    $number = (int) ($attributes['source_row_number'] ?? ($batch->rows()->count() + 2));
    $status = $attributes['status'] ?? P2pkImportRow::STATUS_READY;
    $imagePath = array_key_exists('staging_image_path', $attributes)
        ? $attributes['staging_image_path']
        : "p2pk-imports/{$batch->owner_id}/images/{$number}/asset.jpg";

    $imageSize = null;
    if ($imagePath !== null && ! array_key_exists('skip_image_file', $attributes)) {
        $image = UploadedFile::fake()->image("asset-{$number}.jpg", 160, 120);
        $contents = $image->getContent();
        Storage::disk('local')->put($imagePath, $contents);
        $imageSize = strlen($contents);
    }

    unset($attributes['skip_image_file']);
    $row = $batch->rows()->create(array_replace([
        'source_row_number' => $number,
        'source_fingerprint' => hash('sha256', "source-row-{$batch->id}-{$number}"),
        'status' => $status,
        'is_selected' => true,
        'raw_payload' => ['Nomor Laporan Penilaian' => "LP-{$number}"],
        'mapped_payload' => p2pkFinalizationPayload($number),
        'missing_fields' => $status === P2pkImportRow::STATUS_READY ? [] : [['field' => 'image', 'label' => 'Gambar']],
        'warnings' => [],
        'staging_image_disk' => $imagePath === null ? null : 'local',
        'staging_image_path' => $imagePath,
        'staging_image_original_name' => $imagePath === null ? null : "asset-{$number}.jpg",
        'staging_image_mime' => $imagePath === null ? null : 'image/jpeg',
        'staging_image_size' => $imageSize,
        'attempts' => 0,
    ], $attributes));

    $batch->update([
        'total_rows' => $batch->rows()->count(),
        'selected_rows' => $batch->rows()->where('is_selected', true)->count(),
        'ready_rows' => $batch->rows()
            ->where('is_selected', true)
            ->where('status', P2pkImportRow::STATUS_READY)
            ->count(),
    ]);

    return $row;
}

function runP2pkFinalizationJob(P2pkImportBatch $batch, array $rowIds): void
{
    $job = new ProcessP2pkImportChunk($batch->id, $rowIds);
    app()->call([$job, 'handle']);
}

it('rejects finalization when a selected row is not ready', function () {
    Queue::fake();
    $owner = p2pkFinalizationUser();
    $batch = p2pkFinalizationBatch($owner);
    p2pkFinalizationRow($batch);
    p2pkFinalizationRow($batch, [
        'status' => P2pkImportRow::STATUS_INCOMPLETE,
        'staging_image_path' => null,
    ]);

    $this->actingAs($owner)
        ->post("/home/pembanding-imports/{$batch->id}/finalize", ['confirmed' => true])
        ->assertRedirect()
        ->assertSessionHasErrors('finalize');

    $batch->refresh();
    expect($batch->status)->toBe(P2pkImportBatch::STATUS_DRAFT)
        ->and($batch->finalization_date)->toBeNull()
        ->and($batch->initiated_by)->toBeNull();
    Queue::assertNothingPushed();
    $this->assertDatabaseCount('data_pembanding', 0);
});

it('ignores unselected incomplete rows and dispatches selected rows in chunks of at most ten', function () {
    Queue::fake();
    Carbon::setTestNow(Carbon::parse('2026-07-03 23:55:00', 'Asia/Jakarta'));
    $owner = p2pkFinalizationUser();
    $batch = p2pkFinalizationBatch($owner);
    $selectedIds = collect(range(2, 24))
        ->map(fn (int $number): int => p2pkFinalizationRow($batch, ['source_row_number' => $number])->id);
    p2pkFinalizationRow($batch, [
        'source_row_number' => 25,
        'status' => P2pkImportRow::STATUS_INCOMPLETE,
        'is_selected' => false,
        'staging_image_path' => null,
    ]);

    $this->actingAs($owner)
        ->post("/home/pembanding-imports/{$batch->id}/finalize", ['confirmed' => true])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $jobs = Queue::pushed(ProcessP2pkImportChunk::class);
    $queuedIds = $jobs->flatMap(fn (ProcessP2pkImportChunk $job): array => $job->rowIds)->values();
    expect($jobs)->toHaveCount(3)
        ->and($jobs->map(fn (ProcessP2pkImportChunk $job): int => count($job->rowIds))->all())->toBe([10, 10, 3])
        ->and($queuedIds->sort()->values()->all())->toBe($selectedIds->sort()->values()->all())
        ->and($queuedIds->duplicates())->toBeEmpty();

    $batch->refresh();
    expect($batch->status)->toBe(P2pkImportBatch::STATUS_PROCESSING)
        ->and($batch->finalization_date?->toDateString())->toBe('2026-07-03')
        ->and($batch->initiated_by)->toBe($owner->id)
        ->and($batch->rows()->where('status', P2pkImportRow::STATUS_QUEUED)->count())->toBe(23)
        ->and($batch->rows()->where('status', P2pkImportRow::STATUS_INCOMPLETE)->count())->toBe(1);
});

it('creates the final record with the captured date and removes its staging image', function () {
    $owner = p2pkFinalizationUser();
    $batch = p2pkFinalizationBatch($owner, [
        'status' => P2pkImportBatch::STATUS_PROCESSING,
        'finalization_date' => '2026-07-03',
        'initiated_by' => $owner->id,
    ]);
    $row = p2pkFinalizationRow($batch, ['status' => P2pkImportRow::STATUS_QUEUED]);
    $stagingPath = $row->staging_image_path;

    Carbon::setTestNow('2026-07-04 08:00:00');
    runP2pkFinalizationJob($batch, [$row->id]);

    $row->refresh();
    $batch->refresh();
    $record = $row->pembanding()->firstOrFail();
    expect($row->status)->toBe(P2pkImportRow::STATUS_IMPORTED)
        ->and($row->imported_source_fingerprint)->toBe($row->source_fingerprint)
        ->and($record->tanggal_data?->toDateString())->toBe('2026-07-03')
        ->and($record->created_by)->toBe($owner->id)
        ->and($record->business_fingerprint)->not->toBeNull()
        ->and($record->active_fingerprint)->toBe($record->business_fingerprint)
        ->and($batch->status)->toBe(P2pkImportBatch::STATUS_COMPLETE)
        ->and($batch->imported_rows)->toBe(1)
        ->and($batch->failed_rows)->toBe(0)
        ->and($batch->finalized_at)->not->toBeNull();
    Storage::disk('local')->assertMissing($stagingPath);
    Storage::disk('public')->assertExists($record->getRawOriginal('image'));
});

it('is idempotent when a completed job is delivered more than once', function () {
    $owner = p2pkFinalizationUser();
    $batch = p2pkFinalizationBatch($owner, [
        'status' => P2pkImportBatch::STATUS_PROCESSING,
        'finalization_date' => '2026-07-03',
        'initiated_by' => $owner->id,
    ]);
    $row = p2pkFinalizationRow($batch, ['status' => P2pkImportRow::STATUS_QUEUED]);

    runP2pkFinalizationJob($batch, [$row->id]);
    $firstRecordId = $row->fresh()->pembanding_id;
    runP2pkFinalizationJob($batch, [$row->id]);

    expect($row->fresh()->pembanding_id)->toBe($firstRecordId)
        ->and($row->fresh()->status)->toBe(P2pkImportRow::STATUS_IMPORTED);
    $this->assertDatabaseCount('data_pembanding', 1);
    expect(Storage::disk('public')->allFiles('foto_pembanding'))->toHaveCount(1);
});

it('prevents the same source row from being imported by a different batch', function () {
    $owner = p2pkFinalizationUser();
    $sourceFingerprint = hash('sha256', 'stable-p2pk-source-row');
    $firstBatch = p2pkFinalizationBatch($owner, [
        'status' => P2pkImportBatch::STATUS_PROCESSING,
        'finalization_date' => '2026-07-03',
        'initiated_by' => $owner->id,
    ]);
    $first = p2pkFinalizationRow($firstBatch, ['source_fingerprint' => $sourceFingerprint]);
    runP2pkFinalizationJob($firstBatch, [$first->id]);

    $secondBatch = p2pkFinalizationBatch($owner, [
        'status' => P2pkImportBatch::STATUS_PROCESSING,
        'finalization_date' => '2026-07-04',
        'initiated_by' => $owner->id,
    ]);
    $second = p2pkFinalizationRow($secondBatch, [
        'source_fingerprint' => $sourceFingerprint,
        'mapped_payload' => p2pkFinalizationPayload(99, [
            'alamat_data' => 'Data pelengkap diubah tetapi sumber P2PK tetap sama',
            'harga' => 999999999,
        ]),
    ]);
    runP2pkFinalizationJob($secondBatch, [$second->id]);

    $second->refresh();
    expect($second->status)->toBe(P2pkImportRow::STATUS_SOURCE_ALREADY_IMPORTED)
        ->and($second->pembanding_id)->toBeNull()
        ->and($second->conflicting_pembanding_id)->toBe($first->fresh()->pembanding_id)
        ->and($second->failure_code)->toBe('source_already_imported');
    $this->assertDatabaseCount('data_pembanding', 1);
});

it('rejects an exact final duplicate even when its source fingerprint differs', function () {
    $owner = p2pkFinalizationUser();
    $batch = p2pkFinalizationBatch($owner, [
        'status' => P2pkImportBatch::STATUS_PROCESSING,
        'finalization_date' => '2026-07-03',
        'initiated_by' => $owner->id,
    ]);
    $first = p2pkFinalizationRow($batch, ['source_row_number' => 2]);
    $secondImagePath = "p2pk-imports/{$owner->id}/images/exact-copy/asset.jpg";
    Storage::disk('local')->copy($first->staging_image_path, $secondImagePath);
    $second = p2pkFinalizationRow($batch, [
        'source_row_number' => 3,
        'source_fingerprint' => hash('sha256', 'different-source-row'),
        'mapped_payload' => $first->mapped_payload,
        'staging_image_path' => $secondImagePath,
        'skip_image_file' => true,
    ]);

    runP2pkFinalizationJob($batch, [$first->id, $second->id]);

    expect($first->fresh()->status)->toBe(P2pkImportRow::STATUS_IMPORTED)
        ->and($second->fresh()->status)->toBe(P2pkImportRow::STATUS_FINAL_DUPLICATE)
        ->and($second->fresh()->failure_code)->toBe('final_duplicate')
        ->and($second->fresh()->conflicting_pembanding_id)->toBe($first->fresh()->pembanding_id)
        ->and($batch->fresh()->status)->toBe(P2pkImportBatch::STATUS_PARTIAL)
        ->and($batch->fresh()->imported_rows)->toBe(1)
        ->and($batch->fresh()->failed_rows)->toBe(1);
    $this->assertDatabaseCount('data_pembanding', 1);
});

it('stops retrying a temporary failure after three row attempts', function () {
    Queue::fake();
    $owner = p2pkFinalizationUser();
    $batch = p2pkFinalizationBatch($owner, [
        'status' => P2pkImportBatch::STATUS_PROCESSING,
        'finalization_date' => '2026-07-03',
        'initiated_by' => $owner->id,
    ]);
    $row = p2pkFinalizationRow($batch, ['status' => P2pkImportRow::STATUS_QUEUED]);
    $processor = Mockery::mock(ProcessP2pkImportRowAction::class);
    $processor->shouldReceive('execute')->times(3)->andThrow(new RuntimeException('temporary outage'));
    $summary = app(RefreshP2pkImportBatchSummaryAction::class);

    foreach (range(1, 3) as $attempt) {
        Queue::fake();
        (new ProcessP2pkImportChunk($batch->id, [$row->id]))->handle($processor, $summary);
        expect($row->fresh()->attempts)->toBe($attempt);

        if ($attempt < 3) {
            expect($row->fresh()->status)->toBe(P2pkImportRow::STATUS_QUEUED);
            Queue::assertPushed(ProcessP2pkImportChunk::class, 1);
        }
    }

    expect($row->fresh()->status)->toBe(P2pkImportRow::STATUS_FAILED)
        ->and($row->fresh()->failure_code)->toBe('transient')
        ->and($batch->fresh()->status)->toBe(P2pkImportBatch::STATUS_FAILED);
    Queue::assertNothingPushed();
});

it('allows a failed row to be manually retried as a new three-attempt cycle', function () {
    Queue::fake();
    $owner = p2pkFinalizationUser();
    $batch = p2pkFinalizationBatch($owner, [
        'status' => P2pkImportBatch::STATUS_FAILED,
        'finalization_date' => '2026-07-03',
        'initiated_by' => $owner->id,
        'failed_rows' => 1,
    ]);
    $row = p2pkFinalizationRow($batch, [
        'status' => P2pkImportRow::STATUS_FAILED,
        'attempts' => 3,
        'last_error' => 'Gangguan penyimpanan sementara.',
        'failure_code' => 'transient',
    ]);

    $this->actingAs($owner)
        ->post("/home/pembanding-imports/{$batch->id}/rows/{$row->id}/retry")
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $row->refresh();
    expect($row->status)->toBe(P2pkImportRow::STATUS_QUEUED)
        ->and($row->attempts)->toBe(0)
        ->and($row->last_error)->toBeNull()
        ->and($row->failure_code)->toBeNull()
        ->and($batch->fresh()->status)->toBe(P2pkImportBatch::STATUS_PROCESSING);
    Queue::assertPushed(ProcessP2pkImportChunk::class, function (ProcessP2pkImportChunk $job) use ($batch, $row): bool {
        return $job->batchId === $batch->id && $job->rowIds === [$row->id];
    });
});
