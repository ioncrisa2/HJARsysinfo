<?php

use App\Models\BulkExcelImportBatch;
use App\Models\BulkExcelImportRow;
use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use App\Models\User;
use App\Models\Village;
use App\Services\BulkExcelImport\BulkExcelImportLocationResolver;
use App\Services\BulkExcelImport\BulkExcelImportWorkbookParser;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\PembandingAccessRoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Storage::fake('local');
    $this->seed([MasterDataSeeder::class, PembandingAccessRoleSeeder::class]);

    Province::query()->create(['id' => '99', 'name' => 'Provinsi Test']);
    Regency::query()->create(['id' => '9901', 'province_id' => '99', 'name' => 'Kabupaten Test']);
    District::query()->create(['id' => '9901010', 'regency_id' => '9901', 'name' => 'Kecamatan Test']);
    Village::query()->create(['id' => '9901010001', 'district_id' => '9901010', 'name' => 'Desa Test']);
});

function bulkExcelImportTestRow(array $overrides = []): array
{
    return array_replace([
        'LP-001', 'Tanah Kosong', 'Jalan Contoh 1', '001/002', 'Desa Test',
        'Kecamatan Test', 'Kabupaten Test', 'Provinsi Test', '-6.200000,106.800000',
        100, 0, 120000000, 'Penawaran', 100000000, '01/2025', 'Pemilik', null, null,
    ], $overrides);
}

function bulkExcelImportTestUpload(
    array $rows,
    array $headers = BulkExcelImportWorkbookParser::HEADERS,
    string $name = 'bulk-import.xlsx',
    array $extraSheets = [],
): UploadedFile {
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle(BulkExcelImportWorkbookParser::SHEET_NAME);
    $sheet->fromArray($headers, null, 'A1');
    $sheet->fromArray($rows, null, 'A2');
    foreach ($extraSheets as $extraSheet) {
        $spreadsheet->createSheet()->setTitle($extraSheet)->setCellValue('A1', 'Sheet ini harus diabaikan');
    }

    $path = tempnam(sys_get_temp_dir(), 'bulk-excel-import-test-');
    (new Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();
    $contents = file_get_contents($path);
    unlink($path);

    return UploadedFile::fake()->createWithContent($name, $contents);
}

function bulkExcelImportRoleUser(string $role = 'bulk_import'): User
{
    $user = User::factory()->create(['deactivated_at' => null]);
    $user->assignRole($role);

    return $user;
}

it('seeds the bulk import role and permission', function () {
    $role = Role::findByName('bulk_import');

    expect($role->hasPermissionTo('bulk_import_data::pembanding'))->toBeTrue();
});

it('blocks users without bulk import access', function () {
    $this->actingAs(User::factory()->create())
        ->get('/app/pembanding-imports')
        ->assertForbidden();
});

it('allows bulk import users to load dependent location choices', function () {
    $user = bulkExcelImportRoleUser();

    $this->actingAs($user)
        ->getJson('/app/lookups/regencies?province_id=99')
        ->assertOk()
        ->assertJsonFragment(['value' => '9901']);
});

it('stores workbook rows as drafts without creating main records', function () {
    $user = bulkExcelImportRoleUser();
    $upload = bulkExcelImportTestUpload([
        bulkExcelImportTestRow(),
        bulkExcelImportTestRow([0 => 'LP-002', 2 => 'Jalan Contoh 2']),
    ], name: 'Januari.xlsm');

    $response = $this->actingAs($user)->post('/app/pembanding-imports', ['file' => $upload]);

    $batch = BulkExcelImportBatch::query()->sole();
    $response->assertRedirect(route('app.bulk-excel-imports.show', $batch));
    expect($batch->total_rows)->toBe(2)
        ->and($batch->selected_rows)->toBe(2)
        ->and($batch->rows)->toHaveCount(2)
        ->and(BulkExcelImportRow::query()->where('status', BulkExcelImportRow::STATUS_INCOMPLETE)->count())->toBe(2);
    $this->assertDatabaseCount('data_pembanding', 0);
    Storage::disk('local')->assertExists($batch->source_path);
});

it('reads only Data_Pembanding and ignores every other exported sheet', function () {
    $user = bulkExcelImportRoleUser();
    $upload = bulkExcelImportTestUpload(
        [bulkExcelImportTestRow()],
        name: 'export-lengkap.xlsm',
        extraSheets: ['Cover', 'Rekap', 'Lampiran', 'Referensi'],
    );

    $this->actingAs($user)
        ->post('/app/pembanding-imports', ['file' => $upload])
        ->assertRedirect();

    $batch = BulkExcelImportBatch::query()->sole();
    expect($batch->sheet_name)->toBe(BulkExcelImportWorkbookParser::SHEET_NAME)
        ->and($batch->total_rows)->toBe(1)
        ->and($batch->rows()->sole()->raw_payload['Alamat'])->toBe('Jalan Contoh 1');
});

it('keeps super admin bulk import inside the shared application', function () {
    $superAdmin = bulkExcelImportRoleUser('super_admin');

    $response = $this->actingAs($superAdmin)->get('/app/pembanding-imports');

    $response->assertOk();
    expect($response->viewData('page')['component'])->toBe('PembandingImports/Index');

    $this->actingAs($superAdmin)->post('/app/pembanding-imports', [
        'file' => bulkExcelImportTestUpload([bulkExcelImportTestRow()]),
    ])->assertRedirect('/app/pembanding-imports/1');
});

it('marks exact repeated source rows as unselected duplicates', function () {
    $user = bulkExcelImportRoleUser();
    $first = bulkExcelImportTestRow();

    $this->actingAs($user)->post('/app/pembanding-imports', [
        'file' => bulkExcelImportTestUpload([$first, $first, bulkExcelImportTestRow([0 => 'LP-003'])]),
    ])->assertRedirect();

    $rows = BulkExcelImportRow::query()->orderBy('source_row_number')->get();
    expect($rows)->toHaveCount(3)
        ->and($rows[0]->is_selected)->toBeTrue()
        ->and($rows[1]->status)->toBe(BulkExcelImportRow::STATUS_DUPLICATE)
        ->and($rows[1]->is_selected)->toBeFalse()
        ->and($rows[1]->duplicate_of_row_id)->toBe($rows[0]->id)
        ->and(BulkExcelImportBatch::query()->sole()->selected_rows)->toBe(2);
});

it('parses a 93 row workbook and finds seven repeated source occurrences', function () {
    $user = bulkExcelImportRoleUser();
    $uniqueRows = collect(range(1, 86))
        ->map(fn (int $number): array => bulkExcelImportTestRow([
            0 => 'LP-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
            2 => "Jalan Contoh {$number}",
        ]))
        ->all();
    $rows = [...$uniqueRows, ...array_slice($uniqueRows, 0, 7)];

    $this->actingAs($user)->post('/app/pembanding-imports', [
        'file' => bulkExcelImportTestUpload($rows, name: 'Januari.xlsm'),
    ])->assertRedirect();

    $batch = BulkExcelImportBatch::query()->sole();
    expect($batch->total_rows)->toBe(93)
        ->and($batch->selected_rows)->toBe(86)
        ->and($batch->rows()->where('status', BulkExcelImportRow::STATUS_DUPLICATE)->count())->toBe(7);
    $this->assertDatabaseCount('data_pembanding', 0);
});

it('prioritizes an exact city label over an ambiguous stripped location name', function () {
    Province::query()->create(['id' => '98', 'name' => 'Jawa Contoh']);
    Regency::query()->create(['id' => '9801', 'province_id' => '98', 'name' => 'Kabupaten Bekasi']);
    Regency::query()->create(['id' => '9875', 'province_id' => '98', 'name' => 'Kota Bekasi']);

    $resolved = app(BulkExcelImportLocationResolver::class)->resolve([
        'Propinsi' => 'Jawa Contoh',
        'Kota' => 'Kota Bekasi',
        'Kecamatan' => null,
        'Desa' => null,
    ]);

    expect($resolved['mapped']['regency_id'])->toBe('9875');
});

it('reopens the existing draft when the same user uploads the same file', function () {
    $user = bulkExcelImportRoleUser();
    $contents = bulkExcelImportTestUpload([bulkExcelImportTestRow()])->getContent();

    $this->actingAs($user)->post('/app/pembanding-imports', [
        'file' => UploadedFile::fake()->createWithContent('pertama.xlsx', $contents),
    ])->assertRedirect();
    $batch = BulkExcelImportBatch::query()->sole();

    $this->actingAs($user)->post('/app/pembanding-imports', [
        'file' => UploadedFile::fake()->createWithContent('kedua.xlsx', $contents),
    ])->assertRedirect(route('app.bulk-excel-imports.show', $batch));

    $this->assertDatabaseCount('bulk_excel_import_batches', 1);
    $this->assertDatabaseCount('bulk_excel_import_rows', 1);
});

it('rejects workbooks with a different column contract', function () {
    $user = bulkExcelImportRoleUser();
    $headers = BulkExcelImportWorkbookParser::HEADERS;
    $headers[0] = 'Nomor Berbeda';

    $this->actingAs($user)
        ->from('/app/pembanding-imports')
        ->post('/app/pembanding-imports', ['file' => bulkExcelImportTestUpload([bulkExcelImportTestRow()], $headers)])
        ->assertRedirect('/app/pembanding-imports')
        ->assertSessionHasErrors('file');

    $this->assertDatabaseCount('bulk_excel_import_batches', 0);
    expect(Storage::disk('local')->allFiles('bulk-excel-imports'))->toBeEmpty();
});

it('marks invalid coordinates and unresolved locations for user confirmation', function () {
    $user = bulkExcelImportRoleUser();

    $this->actingAs($user)->post('/app/pembanding-imports', [
        'file' => bulkExcelImportTestUpload([bulkExcelImportTestRow([4 => 'Desa Tidak Ada', 8 => '112.584118,112.584118'])]),
    ])->assertRedirect();

    $row = BulkExcelImportRow::query()->sole();
    expect($row->status)->toBe(BulkExcelImportRow::STATUS_NEEDS_CONFIRMATION)
        ->and(collect($row->warnings)->pluck('field')->all())->toContain('coordinates', 'village_id');
});

it('prevents users from viewing another users draft while allowing super admin recovery', function () {
    $owner = bulkExcelImportRoleUser();
    $other = bulkExcelImportRoleUser();
    $superAdmin = bulkExcelImportRoleUser('super_admin');

    $this->actingAs($owner)->post('/app/pembanding-imports', [
        'file' => bulkExcelImportTestUpload([bulkExcelImportTestRow()]),
    ]);
    $batch = BulkExcelImportBatch::query()->sole();

    $this->actingAs($other)->get("/app/pembanding-imports/{$batch->id}")->assertForbidden();
    $this->actingAs($superAdmin)->get("/app/pembanding-imports/{$batch->id}")->assertOk();
});
