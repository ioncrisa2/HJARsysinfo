<?php

use App\Models\District;
use App\Models\P2pkImportBatch;
use App\Models\P2pkImportRow;
use App\Models\Province;
use App\Models\Regency;
use App\Models\User;
use App\Models\Village;
use App\Services\P2pk\P2pkLocationResolver;
use App\Services\P2pk\P2pkWorkbookParser;
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

function p2pkTestRow(array $overrides = []): array
{
    return array_replace([
        'LP-001', 'Tanah Kosong', 'Jalan Contoh 1', '001/002', 'Desa Test',
        'Kecamatan Test', 'Kabupaten Test', 'Provinsi Test', '-6.200000,106.800000',
        100, 0, 120000000, 'Penawaran', 100000000, '01/2025', 'Pemilik', null, null,
    ], $overrides);
}

function p2pkTestUpload(array $rows, array $headers = P2pkWorkbookParser::HEADERS, string $name = 'p2pk.xlsx'): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle(P2pkWorkbookParser::SHEET_NAME);
    $sheet->fromArray($headers, null, 'A1');
    $sheet->fromArray($rows, null, 'A2');

    $path = tempnam(sys_get_temp_dir(), 'p2pk-test-');
    (new Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();
    $contents = file_get_contents($path);
    unlink($path);

    return UploadedFile::fake()->createWithContent($name, $contents);
}

function p2pkRoleUser(string $role = 'bulk_import'): User
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
        ->get('/home/pembanding-imports')
        ->assertForbidden();
});

it('allows bulk import users to load dependent location choices', function () {
    $user = p2pkRoleUser();

    $this->actingAs($user)
        ->getJson('/home/lookups/regencies?province_id=99')
        ->assertOk()
        ->assertJsonFragment(['value' => '9901']);
});

it('stores workbook rows as drafts without creating main records', function () {
    $user = p2pkRoleUser();
    $upload = p2pkTestUpload([
        p2pkTestRow(),
        p2pkTestRow([0 => 'LP-002', 2 => 'Jalan Contoh 2']),
    ], name: 'Januari.xlsm');

    $response = $this->actingAs($user)->post('/home/pembanding-imports', ['file' => $upload]);

    $batch = P2pkImportBatch::query()->sole();
    $response->assertRedirect(route('home.p2pk-imports.show', $batch));
    expect($batch->total_rows)->toBe(2)
        ->and($batch->selected_rows)->toBe(2)
        ->and($batch->rows)->toHaveCount(2)
        ->and(P2pkImportRow::query()->where('status', P2pkImportRow::STATUS_INCOMPLETE)->count())->toBe(2);
    $this->assertDatabaseCount('data_pembanding', 0);
    Storage::disk('local')->assertExists($batch->source_path);
});

it('keeps super admin bulk import inside the admin panel', function () {
    $superAdmin = p2pkRoleUser('super_admin');

    $response = $this->actingAs($superAdmin)->get('/admin/pembanding-imports');

    $response->assertOk();
    expect($response->viewData('page')['component'])->toBe('PembandingImports/Index')
        ->and($response->viewData('page')['props']['importContext'])->toBe([
            'is_admin' => true,
            'base_url' => '/admin/pembanding-imports',
        ]);

    $this->actingAs($superAdmin)->post('/admin/pembanding-imports', [
        'file' => p2pkTestUpload([p2pkTestRow()]),
    ])->assertRedirect('/admin/pembanding-imports/1');
});

it('marks exact repeated source rows as unselected duplicates', function () {
    $user = p2pkRoleUser();
    $first = p2pkTestRow();

    $this->actingAs($user)->post('/home/pembanding-imports', [
        'file' => p2pkTestUpload([$first, $first, p2pkTestRow([0 => 'LP-003'])]),
    ])->assertRedirect();

    $rows = P2pkImportRow::query()->orderBy('source_row_number')->get();
    expect($rows)->toHaveCount(3)
        ->and($rows[0]->is_selected)->toBeTrue()
        ->and($rows[1]->status)->toBe(P2pkImportRow::STATUS_DUPLICATE)
        ->and($rows[1]->is_selected)->toBeFalse()
        ->and($rows[1]->duplicate_of_row_id)->toBe($rows[0]->id)
        ->and(P2pkImportBatch::query()->sole()->selected_rows)->toBe(2);
});

it('parses a 93 row workbook and finds seven repeated source occurrences', function () {
    $user = p2pkRoleUser();
    $uniqueRows = collect(range(1, 86))
        ->map(fn (int $number): array => p2pkTestRow([
            0 => 'LP-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
            2 => "Jalan Contoh {$number}",
        ]))
        ->all();
    $rows = [...$uniqueRows, ...array_slice($uniqueRows, 0, 7)];

    $this->actingAs($user)->post('/home/pembanding-imports', [
        'file' => p2pkTestUpload($rows, name: 'Januari.xlsm'),
    ])->assertRedirect();

    $batch = P2pkImportBatch::query()->sole();
    expect($batch->total_rows)->toBe(93)
        ->and($batch->selected_rows)->toBe(86)
        ->and($batch->rows()->where('status', P2pkImportRow::STATUS_DUPLICATE)->count())->toBe(7);
    $this->assertDatabaseCount('data_pembanding', 0);
});

it('prioritizes an exact city label over an ambiguous stripped location name', function () {
    Province::query()->create(['id' => '98', 'name' => 'Jawa Contoh']);
    Regency::query()->create(['id' => '9801', 'province_id' => '98', 'name' => 'Kabupaten Bekasi']);
    Regency::query()->create(['id' => '9875', 'province_id' => '98', 'name' => 'Kota Bekasi']);

    $resolved = app(P2pkLocationResolver::class)->resolve([
        'Propinsi' => 'Jawa Contoh',
        'Kota' => 'Kota Bekasi',
        'Kecamatan' => null,
        'Desa' => null,
    ]);

    expect($resolved['mapped']['regency_id'])->toBe('9875');
});

it('reopens the existing draft when the same user uploads the same file', function () {
    $user = p2pkRoleUser();
    $contents = p2pkTestUpload([p2pkTestRow()])->getContent();

    $this->actingAs($user)->post('/home/pembanding-imports', [
        'file' => UploadedFile::fake()->createWithContent('pertama.xlsx', $contents),
    ])->assertRedirect();
    $batch = P2pkImportBatch::query()->sole();

    $this->actingAs($user)->post('/home/pembanding-imports', [
        'file' => UploadedFile::fake()->createWithContent('kedua.xlsx', $contents),
    ])->assertRedirect(route('home.p2pk-imports.show', $batch));

    $this->assertDatabaseCount('p2pk_import_batches', 1);
    $this->assertDatabaseCount('p2pk_import_rows', 1);
});

it('rejects workbooks with a different column contract', function () {
    $user = p2pkRoleUser();
    $headers = P2pkWorkbookParser::HEADERS;
    $headers[0] = 'Nomor Berbeda';

    $this->actingAs($user)
        ->from('/home/pembanding-imports')
        ->post('/home/pembanding-imports', ['file' => p2pkTestUpload([p2pkTestRow()], $headers)])
        ->assertRedirect('/home/pembanding-imports')
        ->assertSessionHasErrors('file');

    $this->assertDatabaseCount('p2pk_import_batches', 0);
    expect(Storage::disk('local')->allFiles('p2pk-imports'))->toBeEmpty();
});

it('marks invalid coordinates and unresolved locations for user confirmation', function () {
    $user = p2pkRoleUser();

    $this->actingAs($user)->post('/home/pembanding-imports', [
        'file' => p2pkTestUpload([p2pkTestRow([4 => 'Desa Tidak Ada', 8 => '112.584118,112.584118'])]),
    ])->assertRedirect();

    $row = P2pkImportRow::query()->sole();
    expect($row->status)->toBe(P2pkImportRow::STATUS_NEEDS_CONFIRMATION)
        ->and(collect($row->warnings)->pluck('field')->all())->toContain('coordinates', 'village_id');
});

it('prevents users from viewing another users draft while allowing super admin recovery', function () {
    $owner = p2pkRoleUser();
    $other = p2pkRoleUser();
    $superAdmin = p2pkRoleUser('super_admin');

    $this->actingAs($owner)->post('/home/pembanding-imports', [
        'file' => p2pkTestUpload([p2pkTestRow()]),
    ]);
    $batch = P2pkImportBatch::query()->sole();

    $this->actingAs($other)->get("/home/pembanding-imports/{$batch->id}")->assertForbidden();
    $this->actingAs($superAdmin)->get("/home/pembanding-imports/{$batch->id}")->assertOk();
});
