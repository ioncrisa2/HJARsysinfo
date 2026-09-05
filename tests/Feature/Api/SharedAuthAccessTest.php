<?php

use App\Models\RefreshToken;
use App\Models\User;
use App\Services\Auth\RefreshTokenService;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Role;

it('blocks deactivated accounts on canonical and legacy profile routes', function (string $prefix, string $method, string $suffix, string $auth) {
    $user = User::factory()->create(['deactivated_at' => now()]);
    if ($auth === 'token') {
        $this->withToken($user->createToken('test')->plainTextToken);
    } else {
        $this->actingAs($user, 'web');
    }

    $this->json($method, $prefix.$suffix, [
        'name' => 'Changed', 'email' => 'changed@example.com',
        'current_password' => 'password', 'password' => 'newpassword123', 'password_confirmation' => 'newpassword123',
    ])->assertForbidden()->assertJsonPath('code', 'USER_DEACTIVATED');

    expect($user->fresh()->email)->toBe($user->email);
    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
})->with(['/api/auth', '/api/v1/auth'])
    ->with([['GET', '/me'], ['PUT', '/profile'], ['PUT', '/profile/password']])
    ->with(['token', 'session']);

it('supports active users on shared and legacy profile routes', function (string $prefix, string $auth) {
    $user = User::factory()->create();
    if ($auth === 'token') {
        $this->withToken($user->createToken('test')->plainTextToken);
    } else {
        $this->actingAs($user, 'web');
    }

    $this->getJson($prefix.'/me')->assertOk()->assertJsonPath('data.id', $user->id);
    $this->putJson($prefix.'/profile', ['name' => 'Updated', 'email' => 'updated@example.com'])
        ->assertOk()->assertJsonPath('data.name', 'Updated');
    $this->putJson($prefix.'/profile/password', [
        'current_password' => 'password', 'password' => 'newpassword123', 'password_confirmation' => 'newpassword123',
    ])->assertOk();
    expect(Hash::check('newpassword123', $user->fresh()->password))->toBeTrue();
})->with(['/api/auth', '/api/v1/auth'])->with(['token', 'session']);

it('does not issue mobile credentials for deactivated accounts', function () {
    $user = User::factory()->create(['deactivated_at' => now()]);
    $user->assignRole(Role::findOrCreate('surveyor', 'web'));
    $this->postJson('/api/auth/login', [
        'email' => $user->email, 'password' => 'password', 'device_name' => 'test',
    ])->assertForbidden();
    expect(PersonalAccessToken::count())->toBe(0)->and(RefreshToken::count())->toBe(0);
});

it('does not refresh mobile credentials after access is withdrawn', function (bool $deactivated) {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('surveyor', 'web'));
    $token = app(RefreshTokenService::class)->create($user->id);
    if ($deactivated) {
        $user->update(['deactivated_at' => now()]);
    } else {
        $user->syncRoles([]);
    }

    $this->postJson('/api/auth/refresh', ['refresh_token' => $token, 'device_name' => 'test'])
        ->assertUnauthorized();
    expect(PersonalAccessToken::count())->toBe(0)->and(RefreshToken::count())->toBe(1);
})->with([true, false]);
