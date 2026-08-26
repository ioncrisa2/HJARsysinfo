<?php

use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\PembandingAccessRoleSeeder;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->seed(PembandingAccessRoleSeeder::class);
    SystemSetting::set('system_mode', 'maintenance');
});

it('allows super admin to access api during maintenance mode', function () {
    $superAdmin = User::factory()->create(['deactivated_at' => null]);
    $superAdmin->assignRole('super_admin');

    $this->actingAs($superAdmin, 'sanctum')
        ->getJson('/api/v1/settings')
        ->assertOk();
});

it('blocks regular authenticated users during maintenance mode with 503 json', function () {
    $user = User::factory()->create(['deactivated_at' => null]);
    $user->assignRole('data_contributor');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/dashboard')
        ->assertStatus(503)
        ->assertJsonPath('code', 'MAINTENANCE_MODE');
});

it('returns 401 unauthenticated for guest api requests without token', function () {
    $this->getJson('/api/v1/dashboard')
        ->assertStatus(401)
        ->assertJsonPath('code', 'UNAUTHENTICATED');
});

it('reads system mode directly from database instead of stale settings cache', function () {
    $user = User::factory()->create(['deactivated_at' => null]);
    $user->assignRole('data_contributor');

    Cache::forever('system_settings', ['system_mode' => 'maintenance']);
    SystemSetting::query()->updateOrCreate(
        ['key' => 'system_mode'],
        ['value' => 'live']
    );

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/dashboard')
        ->assertOk();
});
