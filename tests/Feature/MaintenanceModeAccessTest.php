<?php

use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\PembandingAccessRoleSeeder;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->seed(PembandingAccessRoleSeeder::class);
    SystemSetting::set('system_mode', 'maintenance');
});

it('allows super admin to access admin pages during maintenance mode', function () {
    $superAdmin = User::factory()->create(['deactivated_at' => null]);
    $superAdmin->assignRole('super_admin');

    $this->actingAs($superAdmin)
        ->get('/app/settings')
        ->assertOk();
});

it('blocks regular authenticated users during maintenance mode', function () {
    $user = User::factory()->create(['deactivated_at' => null]);
    $user->assignRole('data_contributor');

    $this->actingAs($user)
        ->get('/app')
        ->assertStatus(503);
});

it('redirects guest admin requests to login during maintenance mode', function () {
    $this->get('/app')
        ->assertRedirect('/login');
});

it('reads system mode directly from database instead of stale settings cache', function () {
    $user = User::factory()->create(['deactivated_at' => null]);
    $user->assignRole('data_contributor');

    Cache::forever('system_settings', ['system_mode' => 'maintenance']);
    SystemSetting::query()->updateOrCreate(
        ['key' => 'system_mode'],
        ['value' => 'live']
    );

    $this->actingAs($user)
        ->get('/app')
        ->assertOk();
});
