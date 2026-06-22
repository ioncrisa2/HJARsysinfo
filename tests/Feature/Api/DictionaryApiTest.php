<?php

use App\Models\Peruntukan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

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
        ->assertJsonPath('status', 'success')
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.slug', 'gudang')
        ->assertJsonPath('data.1.slug', 'rumah_tinggal')
        ->assertJsonMissing(['slug' => 'ruko']);
});

it('rejects inactive dictionary rows without master data permission', function () {
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
        ->assertForbidden()
        ->assertJsonPath('status', 'error');
});

it('can include inactive dictionary rows with active_only=0 for authorized users', function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->user->givePermissionTo(Permission::findOrCreate('view_master_data', 'web'));

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
        ->assertJsonPath('status', 'success')
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment([
            'slug' => 'ruko',
            'is_active' => false,
        ]);
});

it('returns 404 for unknown dictionary type', function () {
    $this->getJson('/api/v1/dictionaries/tidak-ada')
        ->assertStatus(404);
});
