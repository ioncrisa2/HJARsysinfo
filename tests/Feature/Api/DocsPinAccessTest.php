<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    config(['docs.pin' => '09283746', 'docs.session_minutes' => 60]);
});

it('protects the UI and raw specification before unlock', function () {
    $this->get('/docs/api')->assertRedirect('/docs/login');
    $this->get('/docs/api.json')->assertUnauthorized();
    $this->get('/docs/login')->assertOk()->assertSee('PIN akses');
});

it('unlocks docs without granting API authentication and supports logout', function () {
    $this->post('/docs/login', ['pin' => '09283746'])->assertRedirect('/docs/api');
    $this->get('/docs/api')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    $this->getJson('/docs/api.json')->assertOk()->assertJsonPath('openapi', '3.1.0');
    $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    $this->post('/docs/logout')->assertRedirect('/docs/login');
    $this->getJson('/docs/api.json')->assertUnauthorized();
});

it('rejects invalid input without flashing or reflecting the PIN', function ($pin) {
    $this->post('/docs/login', ['pin' => $pin])->assertStatus(422)
        ->assertSessionMissing('_old_input')->assertSessionMissing('docs');
    $this->getJson('/docs/api.json')->assertUnauthorized();
})->with(['wrong' => '88888888', 'short' => '1234', 'array' => [['09283746']], 'empty' => '']);

it('limits guesses even with the correct PIN after the limit and allows retry after cooldown', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->post('/docs/login', ['pin' => '88888888'])->assertStatus(422);
    }
    $this->post('/docs/login', ['pin' => '09283746'])->assertStatus(429)->assertHeader('Retry-After');
    $this->travel(16)->minutes();
    $this->post('/docs/login', ['pin' => '09283746'])->assertRedirect('/docs/api');
});

it('enforces the global limit across IP addresses', function () {
    RateLimiter::increment('docs-pin:global', 900, 100);
    $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.25'])
        ->post('/docs/login', ['pin' => '09283746'])->assertStatus(429);
});

it('expires docs access at the fixed deadline', function () {
    $this->post('/docs/login', ['pin' => '09283746'])->assertRedirect();
    $this->travel(61)->minutes();
    $this->get('/docs/api')->assertRedirect('/docs/login');
    $this->getJson('/docs/api.json')->assertUnauthorized();
});

it('revokes access after PIN rotation', function () {
    $this->post('/docs/login', ['pin' => '09283746'])->assertRedirect();
    config(['docs.pin' => '123456789']);
    $this->getJson('/docs/api.json')->assertUnauthorized();
});

it('falls back to the original gate when PIN access is disabled', function () {
    config(['docs.pin' => '']);
    Gate::define('viewApiDocs', fn (?User $user) => false);
    $this->get('/docs/api')->assertForbidden();
    $this->get('/docs/api.json')->assertForbidden();
    $this->post('/docs/login', ['pin' => '09283746'])->assertForbidden();
    Gate::define('viewApiDocs', fn (?User $user) => true);
    $this->get('/docs/api')->assertOk();
});

it('fails closed for a misconfigured PIN', function () {
    config(['docs.pin' => '1234']);
    $this->post('/docs/login', ['pin' => '1234'])->assertForbidden();
    $this->getJson('/docs/api.json')->assertUnauthorized();
});

it('requires CSRF tokens for unlocking and locking docs', function () {
    $this->app->instance('env', 'production');
    $this->post('/docs/login', ['pin' => '09283746'])->assertStatus(419);
    $this->withSession(['_token' => 'docs-csrf-test'])
        ->post('/docs/login', ['pin' => '09283746', '_token' => 'docs-csrf-test'])->assertRedirect('/docs/api');
    $this->post('/docs/logout')->assertStatus(419);
});
