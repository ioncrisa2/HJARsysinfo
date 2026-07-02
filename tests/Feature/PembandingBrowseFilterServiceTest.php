<?php

use App\Models\Pembanding;
use App\Models\User;
use App\Services\Pembanding\PembandingBrowseFilterService;

it('filters pembanding by creator', function () {
    $creator = User::factory()->create(['name' => 'Creator A']);
    $otherCreator = User::factory()->create(['name' => 'Creator B']);

    $matching = Pembanding::query()->create([
        'nama_pemberi_informasi' => 'Informan A',
        'nomer_telepon_pemberi_informasi' => '081111111111',
        'alamat_data' => 'Jl. Dipilih',
        'latitude' => -2.5,
        'longitude' => 118.0,
        'created_by' => $creator->id,
    ]);

    $excluded = Pembanding::query()->create([
        'nama_pemberi_informasi' => 'Informan B',
        'nomer_telepon_pemberi_informasi' => '082222222222',
        'alamat_data' => 'Jl. Tidak Dipilih',
        'latitude' => -2.6,
        'longitude' => 118.1,
        'created_by' => $otherCreator->id,
    ]);

    $ids = app(PembandingBrowseFilterService::class)
        ->apply(Pembanding::query(), ['created_by' => (string) $creator->id])
        ->pluck('id')
        ->all();

    expect($ids)
        ->toContain($matching->id)
        ->not->toContain($excluded->id);
});

it('normalizes creator filter to an integer value', function () {
    $creator = User::factory()->create();

    $filters = app(PembandingBrowseFilterService::class)
        ->normalize(['created_by' => (string) $creator->id]);

    expect($filters['created_by'])->toBe($creator->id);
});
