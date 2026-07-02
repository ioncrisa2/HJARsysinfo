<?php

use App\Services\Pembanding\PembandingFingerprintService;

it('normalizes equivalent business values into the same fingerprint', function () {
    $service = app(PembandingFingerprintService::class);

    $first = [
        'nama_pemberi_informasi' => '  Budi   Santoso ',
        'nomer_telepon_pemberi_informasi' => '0812-3456-7890',
        'alamat_data' => ' JL. MELATI  10 ',
        'tanggal_data' => '2026-07-02',
        'latitude' => '-6.200000',
        'longitude' => '106.816666',
        'luas_tanah' => '100',
        'harga' => '150000000',
    ];
    $second = [
        'nama_pemberi_informasi' => 'budi santoso',
        'nomer_telepon_pemberi_informasi' => '+62 812 3456 7890',
        'alamat_data' => 'jl. melati 10',
        'tanggal_data' => '2026-07-02',
        'latitude' => -6.2,
        'longitude' => 106.816666,
        'luas_tanah' => '100.00',
        'harga' => 150000000,
    ];

    expect($service->fingerprint($first, 'same-image-checksum'))
        ->toBe($service->fingerprint($second, 'same-image-checksum'));
});

it('changes the fingerprint when one business field changes', function () {
    $service = app(PembandingFingerprintService::class);
    $base = [
        'alamat_data' => 'Jl. Melati 10',
        'latitude' => -6.2,
        'longitude' => 106.816666,
        'harga' => 150000000,
    ];

    expect($service->fingerprint($base, 'same-image-checksum'))
        ->not->toBe($service->fingerprint([
            ...$base,
            'harga' => 160000000,
        ], 'same-image-checksum'))
        ->not->toBe($service->fingerprint($base, 'different-image-checksum'));
});
