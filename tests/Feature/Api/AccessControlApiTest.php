<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

it('lists access control data through Sanctum with web relationship counts', function (string $endpoint, string $auth) {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $user = User::factory()->create();
    $role = Role::findOrCreate('super_admin', 'web');
    $permission = Permission::findOrCreate('view_dashboard', 'web');
    $role->givePermissionTo($permission);
    $user->assignRole($role);
    $user->givePermissionTo($permission);

    if ($auth === 'token') {
        $this->withToken($user->createToken('access-control-test')->plainTextToken);
    } else {
        $this->actingAs($user, 'web');
    }

    $response = $this->getJson('/api/v1/'.$endpoint)->assertOk();
    expect(config('auth.defaults.guard'))->toBe('sanctum');

    $name = $endpoint === 'roles' ? $role->name : $permission->name;
    $item = collect($response->json('data'))->firstWhere('name', $name);
    expect($item)->not->toBeNull()
        ->and($item['guard_name'])->toBe('web')
        ->and($item['users_count'])->toBe(1);

    if ($endpoint === 'roles') {
        expect($item['permissions_count'])->toBe(1)
            ->and($item['permissions'])->toBe(['view_dashboard'])
            ->and($item['is_locked'])->toBeTrue();
    } else {
        expect($item['roles_count'])->toBe(1);
    }
})->with(['roles', 'permissions'])->with(['session', 'token']);

it('rejects access control reads without the required permission', function (string $endpoint) {
    $user = User::factory()->create();

    $this->actingAs($user, 'web')
        ->getJson('/api/v1/'.$endpoint)
        ->assertForbidden();
})->with(['roles', 'permissions']);
