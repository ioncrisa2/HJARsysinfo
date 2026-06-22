<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns 401 for protected auth endpoints without token', function (string $method, string $uri) {
    $response = $method === 'GET'
        ? $this->getJson($uri)
        : $this->postJson($uri);

    $response
        ->assertStatus(401)
        ->assertJsonPath('status', 'error')
        ->assertJsonPath('message', 'Unauthenticated.');
})->with([
    ['GET', '/api/auth/me'],
    ['POST', '/api/auth/logout'],
]);

it('returns 401 for protected pembanding endpoints without token', function (string $method, string $uri) {
    $response = $method === 'GET'
        ? $this->getJson($uri)
        : $this->postJson($uri, [
            'latitude' => -2.5489,
            'longitude' => 118.0149,
            'district_id' => '710101',
            'peruntukan' => 'rumah_tinggal',
        ]);

    $response
        ->assertStatus(401)
        ->assertJsonPath('status', 'error')
        ->assertJsonPath('message', 'Unauthenticated.');
})->with([
    ['GET', '/api/v1/dictionaries/peruntukan'],
    ['GET', '/api/v1/locations/provinces'],
    ['GET', '/api/v1/locations/regencies'],
    ['GET', '/api/v1/locations/districts'],
    ['GET', '/api/v1/locations/villages'],
    ['GET', '/api/v1/pembandings'],
    ['GET', '/api/v1/pembandings/1'],
    ['GET', '/api/v1/pembandings/1/similar'],
    ['POST', '/api/v1/pembandings/similar'],
]);
