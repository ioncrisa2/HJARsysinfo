<?php

use App\Services\P2pk\P2pkValueNormalizer;

it('normalizes Indonesian and international formatted numbers', function (string $value, float $expected) {
    expect((new P2pkValueNormalizer)->number($value))->toBe($expected);
})->with([
    ['Rp 1.250.000', 1250000.0],
    ['1.250.000,50', 1250000.5],
    ['1,250,000.50', 1250000.5],
    ['125,50', 125.5],
    ['125.5', 125.5],
]);

it('rejects coordinates outside valid latitude and longitude ranges', function () {
    $normalizer = new P2pkValueNormalizer;

    expect($normalizer->coordinates('112.584118,112.584118'))->toBeNull()
        ->and($normalizer->coordinates('-6.2,106.8'))->toBe([
            'latitude' => -6.2,
            'longitude' => 106.8,
        ]);
});
