<?php

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);

it('can login with valid credentials and receive tokens', function () {
    $password = 'secret123';
    $user = User::factory()->create([
        'password' => Hash::make($password),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => $password,
        'device_name' => 'pest-test',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonStructure([
            'data' => [
                'token_type',
                'access_token',
                'refresh_token',
                'expires_in',
                'user' => ['id', 'name', 'email'],
            ],
        ]);

    $accessToken = $response->json('data.access_token');
    $expiresIn = $response->json('data.expires_in');
    $tokenId = (int) explode('|', $accessToken)[0];
    $storedToken = PersonalAccessToken::query()->find($tokenId);

    expect($storedToken)->not->toBeNull();
    expect($storedToken?->expires_at)->not->toBeNull();
    expect($storedToken->expires_at->isAfter(now()->addSeconds(max(1, $expiresIn - 120))))->toBeTrue();
});

it('rejects invalid login credentials', function () {
    $user = User::factory()->create([
        'password' => Hash::make('secret123'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
        'device_name' => 'pest-test',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath('message', 'Invalid credentials.');
});

it('validates required fields for login request', function () {
    $response = $this->postJson('/api/auth/login', []);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password', 'device_name']);
});

it('can refresh token and revoke previous refresh token', function () {
    $password = 'secret123';
    $user = User::factory()->create([
        'password' => Hash::make($password),
    ]);

    $login = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => $password,
        'device_name' => 'pest-test',
    ])->assertOk()->json('data');

    $oldRefreshToken = $login['refresh_token'];
    $oldTokenHash = hash('sha256', $oldRefreshToken);

    $response = $this->postJson('/api/auth/refresh', [
        'refresh_token' => $oldRefreshToken,
        'device_name' => 'pest-test-refresh',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonStructure([
            'data' => ['token_type', 'access_token', 'refresh_token', 'expires_in'],
        ]);

    expect($response->json('data.refresh_token'))->not->toBe($oldRefreshToken);

    $newAccessToken = $response->json('data.access_token');
    $newAccessTokenId = (int) explode('|', $newAccessToken)[0];

    expect(PersonalAccessToken::query()->find($newAccessTokenId)?->expires_at)->not->toBeNull();

    $this->assertDatabaseHas('refresh_tokens', [
        'user_id' => $user->id,
        'token_hash' => $oldTokenHash,
        'revoked' => 1,
    ]);
});

it('validates refresh token format', function () {
    $response = $this->postJson('/api/auth/refresh', [
        'refresh_token' => 'short-token',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['refresh_token']);
});

it('returns current user from me endpoint when authenticated', function () {
    $password = 'secret123';
    $user = User::factory()->create([
        'password' => Hash::make($password),
    ]);

    $accessToken = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => $password,
        'device_name' => 'pest-test',
    ])->assertOk()->json('data.access_token');

    $this->withToken($accessToken)
        ->getJson('/api/auth/me')
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email);
});

it('revokes access and refresh tokens on logout', function () {
    $password = 'secret123';
    $user = User::factory()->create([
        'password' => Hash::make($password),
    ]);

    $login = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => $password,
        'device_name' => 'pest-test',
    ])->assertOk()->json('data');

    $accessToken = $login['access_token'];
    $refreshToken = $login['refresh_token'];
    $accessTokenId = (int) explode('|', $accessToken)[0];

    $this->assertDatabaseHas('personal_access_tokens', ['id' => $accessTokenId]);

    $this->withToken($accessToken)
        ->postJson('/api/auth/logout')
        ->assertOk()
        ->assertJsonPath('status', 'success');

    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $accessTokenId]);

    $this->assertDatabaseHas('refresh_tokens', [
        'user_id' => $user->id,
        'token_hash' => hash('sha256', $refreshToken),
        'revoked' => 1,
    ]);

    expect(RefreshToken::query()->where('user_id', $user->id)->where('revoked', false)->exists())
        ->toBeFalse();
});
