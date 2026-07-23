<?php

use App\Models\Pembanding;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function createMapPembanding(User $user, array $overrides = []): Pembanding
{
    return Pembanding::query()->create(array_merge([
        'nama_pemberi_informasi' => 'Sumber Peta',
        'alamat_data' => 'Jl. Peta No. 1',
        'latitude' => -6.200000,
        'longitude' => 106.816666,
        'image' => 'foto_pembanding/peta.jpg',
        'created_by' => $user->id,
    ], $overrides));
}

it('returns lightweight map distribution data for authorized users', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('view_map', 'web'));
    Sanctum::actingAs($user);

    createMapPembanding($user);
    createMapPembanding($user, [
        'alamat_data' => 'Jl. Tanpa Foto',
        'latitude' => -6.210000,
        'longitude' => 106.826666,
        'image' => null,
    ]);

    $response = $this->getJson('/api/v1/pembandings/map');

    $response
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonCount(2, 'data');

    $markers = collect($response->json('data'))->keyBy('alamat_data');

    expect($markers['Jl. Peta No. 1'])
        ->latitude->toBe(-6.2)
        ->longitude->toBe(106.816666)
        ->image_url->toBe(Storage::disk('public')->url('foto_pembanding/peta.jpg'))
        ->and($markers['Jl. Tanpa Foto']['image_url'])->toBeNull();

    expect(array_keys($response->json('data.0')))->toBe([
        'latitude',
        'longitude',
        'alamat_data',
        'image_url',
    ]);
});

it('returns all map distribution data without pagination', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('view_map', 'web'));
    Sanctum::actingAs($user);

    DB::table('data_pembanding')->insert(
        collect(range(1, 205))->map(fn (int $index): array => [
            'nama_pemberi_informasi' => "Sumber {$index}",
            'alamat_data' => "Jl. Peta {$index}",
            'latitude' => -6.2 + ($index / 100000),
            'longitude' => 106.8 + ($index / 100000),
        ])->all()
    );

    $response = $this->getJson('/api/v1/pembandings/map');

    $response
        ->assertOk()
        ->assertJsonCount(205, 'data')
        ->assertJsonMissingPath('data.current_page')
        ->assertJsonMissingPath('data.per_page');
});

it('excludes soft deleted pembanding from map distribution data', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('view_map', 'web'));
    Sanctum::actingAs($user);

    createMapPembanding($user)->delete();

    $this->getJson('/api/v1/pembandings/map')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('requires view map permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('view_any_data::pembanding', 'web'));
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/pembandings/map')
        ->assertForbidden()
        ->assertJsonPath('status', 'error');
});

it('requires authentication', function () {
    $this->getJson('/api/v1/pembandings/map')
        ->assertUnauthorized()
        ->assertJsonPath('status', 'error');
});
