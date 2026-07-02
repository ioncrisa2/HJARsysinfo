<?php

use App\Models\Pembanding;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
});

it('audits without mutation and backfills only non-conflicting active fingerprints', function () {
    Storage::disk('public')->put('foto_pembanding/same-a.jpg', 'same-content');
    Storage::disk('public')->put('foto_pembanding/same-b.jpg', 'same-content');
    Storage::disk('public')->put('foto_pembanding/unique.jpg', 'unique-content');

    $base = [
        'nama_pemberi_informasi' => 'Sumber Data',
        'nomer_telepon_pemberi_informasi' => '08123456789',
        'alamat_data' => 'Jl. Audit',
        'latitude' => -2.5,
        'longitude' => 118.0,
        'luas_tanah' => 100,
        'lebar_depan' => 10,
        'lebar_jalan' => 5,
        'harga' => 100000000,
        'tanggal_data' => '2026-07-02',
    ];

    $first = Pembanding::query()->create([...$base, 'image' => 'foto_pembanding/same-a.jpg']);
    $second = Pembanding::query()->create([...$base, 'image' => 'foto_pembanding/same-b.jpg']);
    $unique = Pembanding::query()->create([
        ...$base,
        'alamat_data' => 'Jl. Audit Berbeda',
        'image' => 'foto_pembanding/unique.jpg',
    ]);
    $originalUpdatedAt = $first->updated_at;

    $this->artisan('pembanding:audit-duplicates', [
        '--report' => 'reports/dry-run.csv',
    ])->assertSuccessful();

    expect($first->refresh()->business_fingerprint)->toBeNull()
        ->and($second->refresh()->business_fingerprint)->toBeNull()
        ->and($unique->refresh()->business_fingerprint)->toBeNull();

    $this->artisan('pembanding:audit-duplicates', [
        '--write' => true,
        '--report' => 'reports/write.csv',
    ])->assertSuccessful();

    expect($first->refresh()->business_fingerprint)->not->toBeNull()
        ->and($first->updated_at->equalTo($originalUpdatedAt))->toBeTrue()
        ->and($first->active_fingerprint)->toBeNull()
        ->and($second->refresh()->business_fingerprint)->toBe($first->business_fingerprint)
        ->and($second->active_fingerprint)->toBeNull()
        ->and($unique->refresh()->active_fingerprint)->toBe($unique->business_fingerprint);

    Storage::disk('local')->assertExists('reports/dry-run.csv');
    Storage::disk('local')->assertExists('reports/write.csv');
});
