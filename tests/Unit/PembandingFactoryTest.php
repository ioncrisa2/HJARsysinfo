<?php

use App\Services\PembandingFactory;

it('preserves missing numeric fields as null', function () {
    $pembanding = (new PembandingFactory)->createFromArray([
        'latitude' => -6.2,
        'longitude' => 106.8,
        'district_id' => '317301',
        'peruntukan' => 'rumah_tinggal',
    ]);

    expect($pembanding->luas_tanah)->toBeNull()
        ->and($pembanding->luas_bangunan)->toBeNull()
        ->and($pembanding->lebar_jalan)->toBeNull()
        ->and($pembanding->harga)->toBeNull();
});

it('preserves explicit zero as a valid value', function () {
    $pembanding = (new PembandingFactory)->createFromArray([
        'latitude' => -6.2,
        'longitude' => 106.8,
        'district_id' => '317301',
        'peruntukan' => 'tanah_kosong',
        'luas_tanah' => 0,
        'luas_bangunan' => 0,
        'lebar_jalan' => 0,
        'harga' => 0,
    ]);

    expect($pembanding->luas_tanah)->toBe(0.0)
        ->and($pembanding->luas_bangunan)->toBe(0.0)
        ->and($pembanding->lebar_jalan)->toBe(0)
        ->and($pembanding->harga)->toBe(0.0);
});
