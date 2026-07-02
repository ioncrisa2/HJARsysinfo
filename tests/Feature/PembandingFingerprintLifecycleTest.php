<?php

use App\Exceptions\DuplicatePembandingException;
use App\Models\Pembanding;
use Illuminate\Database\QueryException;

function fingerprintedPembanding(string $fingerprint, array $overrides = []): Pembanding
{
    return Pembanding::query()->create(array_merge([
        'nama_pemberi_informasi' => 'Sumber Data',
        'nomer_telepon_pemberi_informasi' => '8123456789',
        'alamat_data' => 'Jl. Lifecycle',
        'latitude' => -2.5,
        'longitude' => 118.0,
        'business_fingerprint' => $fingerprint,
        'active_fingerprint' => $fingerprint,
    ], $overrides));
}

it('clears active fingerprint on soft delete and restores it', function () {
    $record = fingerprintedPembanding(str_repeat('a', 64));
    $record->delete();

    expect($record->refresh()->active_fingerprint)->toBeNull();

    $record->restore();

    expect($record->refresh()->active_fingerprint)->toBe($record->business_fingerprint)
        ->and($record->trashed())->toBeFalse();
});

it('rejects restore when an identical active record exists', function () {
    $fingerprint = str_repeat('b', 64);
    $deleted = fingerprintedPembanding($fingerprint);
    $deleted->delete();
    fingerprintedPembanding($fingerprint, ['alamat_data' => 'Direct duplicate fixture']);

    expect(fn () => $deleted->restore())
        ->toThrow(DuplicatePembandingException::class);
});

it('enforces one active row per fingerprint at database level', function () {
    $fingerprint = str_repeat('c', 64);
    fingerprintedPembanding($fingerprint);

    expect(fn () => fingerprintedPembanding($fingerprint, [
        'alamat_data' => 'Concurrent duplicate fixture',
    ]))->toThrow(QueryException::class);
});
