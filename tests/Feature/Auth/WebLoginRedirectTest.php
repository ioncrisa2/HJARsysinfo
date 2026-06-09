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

it('redirects super admin to admin dashboard when intended url is home', function () {
    $user = makeWebLoginUser(['email' => 'admin@example.test'], 'super_admin');

    $this
        ->withSession(['url.intended' => 'http://localhost/home'])
        ->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
        ->assertRedirect(route('admin.dashboard'));
});

it('preserves admin intended urls for super admin users', function () {
    $user = makeWebLoginUser(['email' => 'admin-users@example.test'], 'super_admin');

    $this
        ->withSession(['url.intended' => 'http://localhost/admin/users'])
        ->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
        ->assertRedirect('http://localhost/admin/users');
});

it('does not redirect non admin users into admin urls after login', function () {
    $user = makeWebLoginUser(['email' => 'user@example.test']);

    $this
        ->withSession(['url.intended' => 'http://localhost/admin'])
        ->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
        ->assertRedirect(route('home.dashboard'));
});
