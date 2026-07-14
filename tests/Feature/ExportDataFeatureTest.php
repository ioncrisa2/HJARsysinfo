<?php

use App\Jobs\GeneratePembandingExport;
use App\Models\ExportRun;
use App\Models\Pembanding;
use App\Models\User;
use App\Services\Exports\PembandingExportFileService;
use App\Services\Exports\PembandingExportQueryService;
use App\Support\Exports\PembandingExportColumnRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function exportUser(array $permissions): User
{
    $user = User::factory()->create(['deactivated_at' => null]);
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $user->givePermissionTo($permissions);

    return $user;
}

function exportRecord(User $owner, array $overrides = []): Pembanding
{
    return Pembanding::query()->create(array_merge([
        'nama_pemberi_informasi' => 'Sumber Data',
        'nomer_telepon_pemberi_informasi' => '08123456789',
        'alamat_data' => 'Jalan Pengujian 1',
        'latitude' => -6.2,
        'longitude' => 106.8,
        'harga' => 1000000000,
        'luas_tanah' => 100,
        'tanggal_data' => '2026-07-01',
        'created_by' => $owner->id,
    ], $overrides));
}

it('separates page access from file export permission', function () {
    $viewer = exportUser(['view_export', 'view_any_data::pembanding']);

    $this->actingAs($viewer)->get('/app/export')->assertOk()
        ->assertInertia(fn ($page) => $page->component('Export/Index')->where('can.download', false));

    $this->actingAs($viewer)->get('/app/export/download?format=excel')->assertForbidden();
});

it('uses canonical filters and keeps selected ids inside filter and user scope', function () {
    $owner = exportUser(['view_export', 'export_data::pembanding']);
    $other = User::factory()->create();
    $matching = exportRecord($owner, ['alamat_data' => 'Jalan Mawar']);
    $outsideFilter = exportRecord($owner, ['alamat_data' => 'Jalan Melati']);
    $outsideScope = exportRecord($other, ['alamat_data' => 'Jalan Mawar milik orang lain']);

    $ids = app(PembandingExportQueryService::class)
        ->query($owner, ['q' => 'Mawar'], [$matching->id, $outsideFilter->id, $outsideScope->id])
        ->pluck('id')->all();

    expect($ids)->toBe([$matching->id]);
});

it('searches address and information provider consistently', function () {
    $user = exportUser(['view_export', 'view_any_data::pembanding']);
    exportRecord($user, ['nama_pemberi_informasi' => 'Narasumber Khusus']);

    $this->actingAs($user)->get('/app/export?q=Narasumber+Khusus')->assertOk()
        ->assertInertia(fn ($page) => $page->where('summary.total', 1));
});

it('previews the final backend count and supports completeness scopes', function () {
    $user = exportUser(['export_data::pembanding', 'view_any_data::pembanding']);
    exportRecord($user, ['image' => 'complete.jpg']);
    exportRecord($user, ['image' => null, 'harga' => 0, 'luas_tanah' => 0]);

    $this->actingAs($user)->postJson('/app/export/preview', [
        'format' => 'pdf',
        'mode' => 'detail',
        'scope' => 'filtered',
        'dataset' => 'issues',
    ])->assertOk()
        ->assertJsonPath('count', 1)
        ->assertJsonPath('sync_limit', 100)
        ->assertJsonPath('queued', false);

    expect(app(PembandingExportQueryService::class)->query($user, ['dataset' => 'complete'])->count())->toBe(1);
});

it('delegates the legacy endpoint to the canonical download route', function () {
    $user = exportUser(['export_data::pembanding']);

    $this->actingAs($user)->get('/app/pembanding/export?format=pdf&q=Mawar')
        ->assertRedirect('/app/export/download?format=pdf&q=Mawar&scope=filtered');
});

it('filters sensitive columns and neutralizes spreadsheet formulas', function () {
    $owner = exportUser([]);
    $record = exportRecord($owner, ['alamat_data' => '=HYPERLINK("https://example.test")']);
    $registry = app(PembandingExportColumnRegistry::class);

    expect($registry->resolveColumns($owner, 'kontak'))
        ->not->toContain('nama_pemberi_informasi', 'nomor_telepon');
    expect($registry->map($record, ['alamat'], true)[0])->toStartWith("'=");

    Permission::findOrCreate('export_sensitive_data::pembanding', 'web');
    $owner->givePermissionTo('export_sensitive_data::pembanding');
    expect($registry->resolveColumns($owner->fresh(), 'kontak'))
        ->toContain('nama_pemberi_informasi', 'nomor_telepon');
});

it('creates a private queued export and only lets its owner inspect it', function () {
    Queue::fake();
    $owner = exportUser(['export_data::pembanding', 'view_any_data::pembanding']);
    $stranger = exportUser(['export_data::pembanding']);
    exportRecord($owner);

    $this->actingAs($owner)->post('/app/export/runs', [
        'format' => 'excel',
        'profile' => 'ringkas',
        'scope' => 'filtered',
    ])->assertRedirect();

    $run = ExportRun::query()->sole();
    expect($run->status)->toBe(ExportRun::STATUS_PENDING)
        ->and($run->disk)->toBe('local')
        ->and($run->total_records)->toBe(1);
    Queue::assertPushed(GeneratePembandingExport::class, fn ($job) => $job->exportRunId === $run->id);

    $this->actingAs($owner)->get("/app/export/runs/{$run->id}")->assertOk();
    $this->actingAs($stranger)->get("/app/export/runs/{$run->id}")->assertForbidden();
});

it('generates queued files in private storage and expires them safely', function () {
    Storage::fake('local');
    $owner = exportUser(['export_data::pembanding', 'view_any_data::pembanding']);
    $record = exportRecord($owner);
    $registry = app(PembandingExportColumnRegistry::class);
    $run = ExportRun::query()->create([
        'user_id' => $owner->id,
        'status' => ExportRun::STATUS_PENDING,
        'format' => 'csv',
        'mode' => 'summary',
        'profile' => 'ringkas',
        'scope' => 'selected',
        'filters' => [],
        'selected_ids' => [$record->id],
        'columns' => $registry->resolveColumns($owner, 'ringkas'),
        'snapshot_at' => now(),
        'total_records' => 1,
        'disk' => 'local',
    ]);

    app()->call([new GeneratePembandingExport($run->id), 'handle']);
    $run->refresh();

    expect($run->status)->toBe(ExportRun::STATUS_COMPLETED)
        ->and($run->checksum)->toHaveLength(64)
        ->and($run->expires_at)->not->toBeNull();
    Storage::disk('local')->assertExists($run->path);

    $run->update(['expires_at' => now()->subMinute()]);
    $this->artisan('exports:cleanup')->assertSuccessful();
    expect($run->fresh()->status)->toBe(ExportRun::STATUS_EXPIRED);
    Storage::disk('local')->assertMissing($run->path);
});

it('exports valid geojson coordinates in longitude latitude order', function () {
    $user = exportUser(['export_data::pembanding', 'view_any_data::pembanding']);
    exportRecord($user, ['longitude' => 106.8, 'latitude' => -6.2]);

    $response = $this->actingAs($user)->get('/app/export/download?format=geojson&profile=geospasial&scope=filtered');
    $response->assertOk();
    ob_start();
    $response->baseResponse->sendContent();
    $contents = ob_get_clean();
    $data = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    expect($data['type'])->toBe('FeatureCollection')
        ->and($data['features'][0]['geometry']['coordinates'])->toBe([106.8, -6.2]);
    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'export',
        'event' => 'downloaded',
        'causer_id' => $user->id,
    ]);
});

it('exports escaped KML points and rejects unsafe direct PDF sizes', function () {
    $user = exportUser(['export_data::pembanding', 'view_any_data::pembanding']);
    exportRecord($user, ['alamat_data' => 'Tanah & Bangunan']);

    $response = $this->actingAs($user)->get('/app/export/download?format=kml&profile=geospasial&scope=filtered');
    ob_start();
    $response->baseResponse->sendContent();
    $kml = ob_get_clean();
    expect($kml)->toContain('<kml xmlns="http://www.opengis.net/kml/2.2">')
        ->toContain('<name>Tanah &amp; Bangunan</name>')
        ->toContain('<coordinates>106.8,-6.2,0</coordinates>');

    $rows = collect(range(1, 100))->map(fn (int $index): array => [
        'nama_pemberi_informasi' => "Sumber {$index}",
        'nomer_telepon_pemberi_informasi' => '08123',
        'alamat_data' => "Alamat {$index}",
        'latitude' => -6.2,
        'longitude' => 106.8,
        'created_by' => $user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ])->all();
    Pembanding::query()->insert($rows);

    $this->actingAs($user)->getJson('/app/export/download?format=pdf&mode=detail&scope=filtered')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('scope');
});

it('creates a styled Excel workbook with typed values metadata and safe text', function () {
    Storage::fake('local');
    $user = exportUser(['view_any_data::pembanding']);
    exportRecord($user, ['alamat_data' => '=2+2', 'harga' => 1250000000, 'tanggal_data' => '2026-07-01']);
    $query = app(PembandingExportQueryService::class)->query($user, []);

    $file = app(PembandingExportFileService::class)->storeQuery(
        $query,
        'excel',
        'summary',
        ['alamat', 'harga', 'tanggal_data'],
        ['Diminta oleh' => $user->name],
        'local',
        'exports/test',
    );

    $workbook = IOFactory::load(Storage::disk('local')->path($file['path']));
    $data = $workbook->getSheetByName('Data Pembanding');

    expect($workbook->getSheetCount())->toBe(2)
        ->and($data->getCell('A1')->getValue())->toBe('Alamat')
        ->and($data->getCell('A2')->getValue())->toBe("'=2+2")
        ->and($data->getCell('B2')->getValue())->toBeNumeric()
        ->and($data->getStyle('B2')->getNumberFormat()->getFormatCode())->toBe('#,##0')
        ->and($data->getCell('C2')->getValue())->toBeNumeric()
        ->and($data->getFreezePane())->toBe('A2')
        ->and($data->getAutoFilter()->getRange())->toBe('A1:C1');
});

it('renders summary and detail pdf files with export identity', function (string $mode) {
    Storage::fake('local');
    $user = exportUser(['view_any_data::pembanding']);
    exportRecord($user);
    $query = app(PembandingExportQueryService::class)->query($user, []);

    $file = app(PembandingExportFileService::class)->storeQuery(
        $query,
        'pdf',
        $mode,
        ['id', 'alamat', 'luas_tanah', 'harga', 'tanggal_data'],
        ['Diminta oleh' => 'Penguji', 'Dibuat pada' => '2026-07-14 12:00:00 WIB', 'Filter' => []],
        'local',
        'exports/pdf-test',
    );

    expect(Storage::disk('local')->get($file['path']))->toStartWith('%PDF-');
})->with(['summary', 'detail']);
