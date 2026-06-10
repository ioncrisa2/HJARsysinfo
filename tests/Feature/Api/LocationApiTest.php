<?php

use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    
    // Setup some data
    $this->province = Province::create(['id' => 11, 'name' => 'ACEH']);
    $this->regency = Regency::create(['id' => 1101, 'province_id' => 11, 'name' => 'KABUPATEN SIMEULUE']);
    $this->district = District::create(['id' => 1101010, 'regency_id' => 1101, 'name' => 'TEUPAH SELATAN']);
    $this->village = Village::create(['id' => 1101010001, 'district_id' => 1101010, 'name' => 'LATIUNG']);
});

it('can fetch provinces', function () {
    $response = $this->actingAs($this->user)->getJson('/api/v1/locations/provinces');

    $response
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonFragment([
            'id' => '11',
            'name' => 'ACEH'
        ]);
});

it('can fetch regencies with or without province filter', function () {
    $response = $this->actingAs($this->user)->getJson('/api/v1/locations/regencies');
    $response->assertOk()->assertJsonFragment(['id' => '1101', 'name' => 'KABUPATEN SIMEULUE']);

    $responseFilter = $this->actingAs($this->user)->getJson('/api/v1/locations/regencies?province_id=11');
    $responseFilter->assertOk()->assertJsonFragment(['id' => '1101', 'name' => 'KABUPATEN SIMEULUE']);

    $responseEmpty = $this->actingAs($this->user)->getJson('/api/v1/locations/regencies?province_id=99');
    $responseEmpty->assertOk()->assertJsonCount(0, 'data');
});

it('can fetch districts with or without regency filter', function () {
    $response = $this->actingAs($this->user)->getJson('/api/v1/locations/districts');
    $response->assertOk()->assertJsonFragment(['id' => '1101010', 'name' => 'TEUPAH SELATAN']);

    $responseFilter = $this->actingAs($this->user)->getJson('/api/v1/locations/districts?regency_id=1101');
    $responseFilter->assertOk()->assertJsonFragment(['id' => '1101010', 'name' => 'TEUPAH SELATAN']);

    $responseEmpty = $this->actingAs($this->user)->getJson('/api/v1/locations/districts?regency_id=9999');
    $responseEmpty->assertOk()->assertJsonCount(0, 'data');
});

it('can fetch villages with or without district filter', function () {
    $response = $this->actingAs($this->user)->getJson('/api/v1/locations/villages');
    $response->assertOk()->assertJsonFragment(['id' => '1101010001', 'name' => 'LATIUNG']);

    $responseFilter = $this->actingAs($this->user)->getJson('/api/v1/locations/villages?district_id=1101010');
    $responseFilter->assertOk()->assertJsonFragment(['id' => '1101010001', 'name' => 'LATIUNG']);

    $responseEmpty = $this->actingAs($this->user)->getJson('/api/v1/locations/villages?district_id=9999999');
    $responseEmpty->assertOk()->assertJsonCount(0, 'data');
});
