<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

function createSessionApiTestUser(array $attributes = []): User
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $permission = Permission::findOrCreate('view_dashboard', 'web');
    $role = Role::findOrCreate('app_user', 'web');
    $role->givePermissionTo($permission);
    $user = User::factory()->create($attributes);
    $user->assignRole($role);

    return $user;
}

it('issues csrf and session cookies for an allowed SPA origin', function () {
    $this->withHeader('Origin', 'http://localhost:5173')
        ->get('/sanctum/csrf-cookie')
        ->assertNoContent()
        ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173')
        ->assertHeader('Access-Control-Allow-Credentials', 'true')
        ->assertCookie('XSRF-TOKEN')
        ->assertCookie(config('session.cookie'));
});

it('returns the same unauthenticated contract with and without an origin', function (?string $origin) {
    $request = $origin ? $this->withHeader('Origin', $origin) : $this;
    $response = $request->getJson('/api/v1/auth/me')
        ->assertUnauthorized()
        ->assertExactJson([
            'status' => 'error',
            'code' => 'UNAUTHENTICATED',
            'message' => 'Unauthenticated.',
            'errors' => null,
        ]);

    if ($origin) {
        $response->assertHeader('Access-Control-Allow-Origin', $origin);
    }
})->with(['without origin' => null, 'allowed origin' => 'http://localhost:5173']);

it('logs a web user in, exposes effective access, and regenerates the session id', function () {
    $password = 'secret123';
    $user = createSessionApiTestUser(['password' => Hash::make($password)]);
    $oldSessionId = session()->getId();

    $this->postJson('/api/v1/auth/session', ['email' => $user->email, 'password' => $password])
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.roles.0', 'app_user')
        ->assertJsonPath('data.permissions.0', 'view_dashboard')
        ->assertJsonMissingPath('data.access_token')
        ->assertJsonMissingPath('data.refresh_token');

    expect(session()->getId())->not->toBe($oldSessionId);
    $this->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('data.id', $user->id);
});

it('rejects bad credentials with a generic response', function () {
    $user = createSessionApiTestUser(['password' => Hash::make('secret123')]);
    $this->postJson('/api/v1/auth/session', ['email' => $user->email, 'password' => 'incorrect-password'])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'INVALID_CREDENTIALS')
        ->assertJsonPath('message', 'Email atau password tidak valid.');
});

it('rejects a deactivated user and leaves no authenticated session', function () {
    $password = 'secret123';
    $user = createSessionApiTestUser(['password' => Hash::make($password), 'deactivated_at' => now()]);
    $this->postJson('/api/v1/auth/session', ['email' => $user->email, 'password' => $password])
        ->assertForbidden()
        ->assertJsonPath('code', 'USER_DEACTIVATED');
    $this->getJson('/api/v1/auth/me')->assertUnauthorized();
});

it('invalidates the authenticated session on logout', function () {
    $user = createSessionApiTestUser();
    $this->actingAs($user, 'web');
    session(['_token' => 'valid-csrf-token', 'logout_test_marker' => true]);
    $this->withHeader('Origin', 'http://localhost:5173')
        ->withHeader('X-CSRF-TOKEN', 'valid-csrf-token')
        ->deleteJson('/api/v1/auth/session')
        ->assertOk()
        ->assertSessionMissing('logout_test_marker');
    $this->assertGuest('web');
});

it('rejects a stateful mutation with an invalid csrf token', function () {
    // Laravel skips CSRF verification while running unit tests. Exercise the
    // production middleware branch explicitly for this security assertion.
    $this->app->detectEnvironment(fn () => 'local');

    $user = createSessionApiTestUser();
    $this->actingAs($user, 'web');
    session(['_token' => 'valid-csrf-token']);

    $this->withHeader('Origin', 'http://localhost:5173')
        ->withHeader('X-CSRF-TOKEN', 'invalid-csrf-token')
        ->deleteJson('/api/v1/auth/session')
        ->assertStatus(419)
        ->assertJsonPath('code', 'CSRF_MISMATCH');
});

it('answers allowed preflight and omits cors headers for another origin', function () {
    $this->call('OPTIONS', '/api/v1/auth/session', [], [], [], [
        'HTTP_ORIGIN' => 'http://localhost:5173',
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'content-type,x-xsrf-token',
    ])
        ->assertNoContent()
        ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173')
        ->assertHeader('Access-Control-Allow-Credentials', 'true');

    $this->withHeader('Origin', 'https://untrusted.example')
        ->getJson('/api/v1/auth/me')
        ->assertUnauthorized()
        ->assertHeaderMissing('Access-Control-Allow-Origin');
});
