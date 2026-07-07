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

it('redirects every role to the shared application dashboard', function (?string $role) {
    $user = makeWebLoginUser(['email' => ($role ?? 'regular').'@example.test'], $role);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect('/app');
})->with(['super_admin', 'surveyor', null]);

it('preserves an intended application url for any authenticated role', function () {
    $user = makeWebLoginUser(['email' => 'user@example.test']);

    $intendedUrl = url('/app/users');

    $this->withSession(['url.intended' => $intendedUrl])
        ->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
        ->assertRedirect($intendedUrl);
});

it('does not preserve legacy or external intended urls', function (string $intendedUrl) {
    $user = makeWebLoginUser(['email' => md5($intendedUrl).'@example.test']);

    $this->withSession(['url.intended' => $intendedUrl])
        ->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
        ->assertRedirect('/app');
})->with([
    'legacy home' => 'http://localhost/home',
    'legacy admin' => 'http://localhost/admin/users',
    'external host' => 'https://example.org/app/users',
]);
