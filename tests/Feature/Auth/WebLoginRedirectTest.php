<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function makeWebLoginUser(array $attributes = [], ?string $role = null): User
{
    $user = User::factory()->create(array_merge([
        'password' => 'password',
        'deactivated_at' => null,
    ], $attributes));

    if ($role !== null) {
        $roleModel = Role::query()->firstOrCreate([
            'name' => $role,
            'guard_name' => 'web',
        ]);
        $user->assignRole($roleModel);
    }

    return $user;
}

it('allows valid users to establish an authenticated web session via api', function (?string $role) {
    $user = makeWebLoginUser(['email' => ($role ?? 'regular').'@example.test'], $role);

    $response = $this->postJson('/api/v1/auth/session', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.email', $user->email);

    $this->assertAuthenticatedAs($user, 'web');
})->with(['super_admin', 'pimpinan', 'data_contributor', 'surveyor', 'bulk_import', null]);

it('rejects invalid credentials for web session', function () {
    $user = makeWebLoginUser(['email' => 'user@example.test']);

    $response = $this->postJson('/api/v1/auth/session', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('status', 'error')
        ->assertJsonPath('code', 'INVALID_CREDENTIALS');

    $this->assertGuest('web');
});

it('allows authenticated session to logout and clear session', function () {
    $user = makeWebLoginUser();

    $this->actingAs($user, 'web')
        ->deleteJson('/api/v1/auth/session')
        ->assertOk()
        ->assertJsonPath('status', 'success');

    $this->assertGuest('web');
});
