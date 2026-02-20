<?php

use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Village;
use App\Services\Location\LocationIdGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates village id with 3 digit suffix for 7 digit district ids', function () {
    Province::query()->create([
        'id' => '33',
        'name' => 'Jawa Tengah',
    ]);

    Regency::query()->create([
        'id' => '3326',
        'province_id' => '33',
        'name' => 'Kabupaten Pekalongan',
    ]);

    District::query()->create([
        'id' => '3326070',
        'regency_id' => '3326',
        'name' => 'Kecamatan Test',
    ]);

    Village::query()->create([
        'id' => '3326070001',
        'district_id' => '3326070',
        'name' => 'Desa 1',
    ]);

    Village::query()->create([
        'id' => '3326070003',
        'district_id' => '3326070',
        'name' => 'Desa 3',
    ]);

    $nextId = app(LocationIdGenerator::class)->nextVillageId('3326070');

    expect($nextId)->toBe('3326070002')
        ->and(strlen($nextId))->toBe(10);
});

it('generates district id with 3 digit suffix for 4 digit regency ids', function () {
    Province::query()->create([
        'id' => '71',
        'name' => 'Sulawesi Utara',
    ]);

    Regency::query()->create([
        'id' => '7101',
        'province_id' => '71',
        'name' => 'Kabupaten Test',
    ]);

    District::query()->create([
        'id' => '7101001',
        'regency_id' => '7101',
        'name' => 'Kecamatan 1',
    ]);

    District::query()->create([
        'id' => '7101003',
        'regency_id' => '7101',
        'name' => 'Kecamatan 3',
    ]);

    $nextId = app(LocationIdGenerator::class)->nextDistrictId('7101');

    expect($nextId)->toBe('7101002')
        ->and(strlen($nextId))->toBe(7);
});
