<?php

use App\Models\Peruntukan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

it('returns active dictionary rows by default', function () {
    Peruntukan::query()->create([
        'slug' => 'gudang',
        'name' => 'Gudang',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    Peruntukan::query()->create([
        'slug' => 'ruko',
        'name' => 'Ruko',
        'sort_order' => 2,
        'is_active' => false,
    ]);

    Peruntukan::query()->create([
        'slug' => 'rumah_tinggal',
        'name' => 'Rumah Tinggal',
        'sort_order' => 3,
        'is_active' => true,
    ]);

    $response = $this->getJson('/api/v1/dictionaries/peruntukan');

    $response
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonPath('0.slug', 'gudang')
        ->assertJsonPath('1.slug', 'rumah_tinggal')
        ->assertJsonMissing(['slug' => 'ruko']);
});

it('can include inactive dictionary rows with active_only=0', function () {
    Peruntukan::query()->create([
        'slug' => 'gudang',
        'name' => 'Gudang',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    Peruntukan::query()->create([
        'slug' => 'ruko',
        'name' => 'Ruko',
        'sort_order' => 2,
        'is_active' => false,
    ]);

    $response = $this->getJson('/api/v1/dictionaries/peruntukan?active_only=0');

    $response
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonFragment([
            'slug' => 'ruko',
            'is_active' => false,
        ]);
});

it('returns 404 for unknown dictionary type', function () {
    $this->getJson('/api/v1/dictionaries/tidak-ada')
        ->assertStatus(404);
});
