<?php

use App\Models\District;
use App\Models\Integration;
use App\Models\IntegrationKey;
use App\Models\Pembanding;
use App\Models\Peruntukan;
use App\Models\Province;
use App\Models\Regency;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Auth\IntegrationKeyService;
use App\Support\IntegrationAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Psr\Log\NullLogger;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    config()->set('logging.channels.integration', ['driver' => 'null']);
    $this->admin = User::factory()->create();
    Permission::findOrCreate('manage_integrations', 'web');
    $this->admin->givePermissionTo('manage_integrations');
    $this->integration = Integration::create(['name' => 'Penilaian', 'created_by' => $this->admin->id]);
    $this->issue = fn (array $scopes = IntegrationAccess::SCOPES) => app(IntegrationKeyService::class)
        ->issue($this->integration, $this->admin, [
            'name' => 'Testing', 'scopes' => $scopes, 'expires_at' => now()->addDay(),
        ]);
    Province::create(['id' => '71', 'name' => 'Sulawesi Utara']);
    Regency::create(['id' => '7101', 'province_id' => '71', 'name' => 'Kabupaten Test']);
    District::create(['id' => '710101', 'regency_id' => '7101', 'name' => 'Kecamatan Test']);
    $peruntukan = Peruntukan::create(['slug' => 'rumah_tinggal', 'name' => 'Rumah Tinggal']);
    $this->reference = Pembanding::create([
        'nama_pemberi_informasi' => 'Sumber Rahasia', 'nomer_telepon_pemberi_informasi' => '08123456789',
        'alamat_data' => 'Alamat properti', 'catatan' => 'Catatan internal', 'created_by' => $this->admin->id,
        'latitude' => -2.5489, 'longitude' => 118.0149, 'district_id' => '710101',
        'regency_id' => '7101', 'peruntukan_id' => $peruntukan->id, 'luas_tanah' => 120,
        'harga' => 500000000, 'tanggal_data' => now()->toDateString(),
    ]);
    $this->candidate = $this->reference->replicate();
    $this->candidate->latitude = -2.55;
    $this->candidate->save();
    $this->payload = [
        'latitude' => -2.5489, 'longitude' => 118.0149, 'district_id' => '710101',
        'peruntukan' => 'rumah_tinggal', 'luas_tanah' => 120, 'limit' => 10,
    ];
});

it('serves shared data endpoints using only the matching integration scope', function (string $scope, string $method, string $path) {
    $issued = ($this->issue)([$scope]);
    $path = str_replace('{id}', (string) $this->reference->id, $path);
    $this->withToken($issued['plain_text_key'])->json($method, '/api/v1/'.$path, $method === 'POST' ? $this->payload : [])
        ->assertOk()->assertJsonPath('status', 'success');
    expect($issued['key']->fresh()->last_used_at)->not->toBeNull();
})->with([
    ['pembandings:read', 'GET', 'pembandings'],
    ['pembandings:read', 'GET', 'pembandings/{id}'],
    ['pembandings:similar', 'POST', 'pembandings/similar'],
    ['pembandings:similar', 'GET', 'pembandings/{id}/similar'],
    ['locations:read', 'GET', 'locations/provinces'],
    ['locations:read', 'GET', 'locations/regencies'],
    ['locations:read', 'GET', 'locations/districts'],
    ['locations:read', 'GET', 'locations/villages'],
    ['dictionaries:read', 'GET', 'dictionaries/peruntukan'],
]);

it('does not authenticate as a user on user or administrative endpoints', function (string $method, string $path) {
    $issued = ($this->issue)();
    $this->withToken($issued['plain_text_key'])
        ->json($method, '/api/'.$path)->assertUnauthorized();
})->with([
    ['GET', 'v1/auth/me'],
    ['GET', 'v1/users'], ['GET', 'v1/integrations'], ['POST', 'v1/integrations'],
    ['GET', 'v1/settings'], ['GET', 'v1/geo'], ['GET', 'v1/exports/configuration'],
    ['POST', 'v1/pembandings'], ['PUT', 'v1/pembandings/1'], ['DELETE', 'v1/pembandings/1'],
    ['POST', 'v1/pembandings/1'], ['GET', 'v1/pembandings/map'],
    ['GET', 'v1/pembandings/form-options'], ['GET', 'v1/pembandings/1/history'],
    ['POST', 'v1/dictionaries/peruntukan'], ['GET', 'v1/dictionaries'],
]);

it('rejects integration credentials on public authentication endpoints', function (string $path) {
    $issued = ($this->issue)();
    $this->withToken($issued['plain_text_key'])->postJson('/api/'.$path)->assertForbidden();
})->with(['auth/login', 'auth/refresh']);

it('rejects insufficient scopes even when an admin browser session exists', function (string $path, string $scope) {
    $this->admin->assignRole(Role::findOrCreate('super_admin', 'web'));
    $issued = ($this->issue)([$scope]);
    $this->actingAs($this->admin, 'web')->withToken($issued['plain_text_key'])
        ->getJson('/api/v1/'.$path)->assertForbidden();
})->with([
    ['pembandings', 'pembandings:similar'], ['pembandings/1/similar', 'pembandings:read'],
    ['locations/districts', 'dictionaries:read'], ['dictionaries/peruntukan', 'locations:read'],
    ['integrations', 'pembandings:read'],
]);

it('rejects invalid keys without falling back to the user session', function (string $state) {
    $issued = ($this->issue)();
    $token = $issued['plain_text_key'];
    match ($state) {
        'revoked' => $issued['key']->update(['revoked_at' => now()]),
        'expired' => $issued['key']->update(['expires_at' => now()->subSecond()]),
        'disabled' => $this->integration->update(['is_active' => false]),
        'unknown' => $token = IntegrationAccess::PREFIX.str_repeat('a', 64),
        'malformed' => $token = IntegrationAccess::PREFIX.'invalid',
    };
    $this->actingAs($this->admin, 'web')->withToken($token)
        ->getJson('/api/v1/locations/provinces')->assertUnauthorized();
})->with(['revoked', 'expired', 'disabled', 'unknown', 'malformed']);

it('preserves scores while excluding internal fields on all integration pembanding responses', function () {
    $this->admin->givePermissionTo(Permission::findOrCreate('view_any_data::pembanding', 'web'));
    $userResponse = $this->actingAs($this->admin, 'web')
        ->postJson('/api/v1/pembandings/similar', $this->payload)->assertOk();
    $userResponse->assertJsonPath('data.0.nama_pemberi_informasi', 'Sumber Rahasia');
    $issued = ($this->issue)();
    $response = $this->withToken($issued['plain_text_key'])
        ->postJson('/api/v1/pembandings/similar', $this->payload)->assertOk();
    expect(collect($response->json('data'))->pluck('similarity_score')->all())
        ->toBe(collect($userResponse->json('data'))->pluck('similarity_score')->all());
    foreach (['nama_pemberi_informasi', 'nomer_telepon_pemberi_informasi', 'catatan', 'created_by'] as $field) {
        $response->assertJsonMissingPath('data.0.'.$field);
        $this->getJson('/api/v1/pembandings')->assertOk()->assertJsonMissingPath('data.0.'.$field);
        $this->getJson('/api/v1/pembandings/'.$this->reference->id)->assertOk()->assertJsonMissingPath('data.'.$field);
        $this->getJson('/api/v1/pembandings/'.$this->reference->id.'/similar')->assertOk()->assertJsonMissingPath('data.0.'.$field);
    }
    expect(Pembanding::count())->toBe(2);
});

it('does not expose inactive dictionary values through an admin cookie plus integration key', function () {
    $this->admin->assignRole(Role::findOrCreate('super_admin', 'web'));
    Peruntukan::create(['slug' => 'ruko', 'name' => 'Ruko', 'is_active' => false]);
    $issued = ($this->issue)(['dictionaries:read']);
    $this->actingAs($this->admin, 'web')->withToken($issued['plain_text_key'])
        ->getJson('/api/v1/dictionaries/peruntukan')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/dictionaries/peruntukan?active_only=0')->assertForbidden();
});

it('enforces per key rate limits and maintenance mode', function () {
    $this->integration->update(['requests_per_minute' => 1]);
    $first = ($this->issue)(['locations:read']);
    $second = ($this->issue)(['locations:read']);
    $this->withToken($first['plain_text_key'])->getJson('/api/v1/locations/provinces')->assertOk();
    $this->getJson('/api/v1/locations/provinces')->assertStatus(429)->assertHeader('Retry-After');
    $this->withToken($second['plain_text_key'])->getJson('/api/v1/locations/provinces')->assertOk();
    $this->travel(61)->seconds();
    $this->withToken($first['plain_text_key'])->getJson('/api/v1/locations/provinces')->assertOk();
    $this->travel(61)->seconds();
    SystemSetting::set('system_mode', 'maintenance');
    $this->admin->assignRole(Role::findOrCreate('super_admin', 'web'));
    $this->actingAs($this->admin, 'web')->getJson('/api/v1/locations/provinces')
        ->assertStatus(503)->assertJsonPath('code', 'MAINTENANCE_MODE');
});

it('allows admins to issue rotate and revoke keys without exposing secrets again', function () {
    $this->actingAs($this->admin, 'web');
    $integration = $this->postJson('/api/v1/integrations', ['name' => 'Aplikasi Baru'])
        ->assertCreated()->json('data');
    $path = '/api/v1/integrations/'.$integration['id'];
    $body = ['name' => 'Utama', 'scopes' => ['locations:read'], 'expires_at' => now()->addDays(30)->toIso8601String()];
    $first = $this->postJson($path.'/keys', $body)->assertCreated()->assertHeader('Cache-Control', 'no-store, private')->json('data');
    $second = $this->postJson($path.'/keys', $body)->assertCreated()->json('data');
    $record = IntegrationKey::findOrFail($first['key']['id']);
    expect($record->key_hash)->toBe(hash('sha256', $first['plain_text_key']))
        ->and($record->key_hash)->not->toBe($first['plain_text_key'])
        ->and($second['plain_text_key'])->not->toBe($first['plain_text_key']);
    $detail = $this->getJson($path)->assertOk()->assertJsonCount(2, 'data.keys');
    expect($detail->getContent())->not->toContain($first['plain_text_key'], 'key_hash', 'plain_text_key');
    $this->getJson('/api/v1/integrations')->assertOk()->assertJsonPath('meta.total', 2);
    $this->getJson('/api/v1/integrations/scopes')->assertOk()->assertJsonCount(4, 'data');
    $this->deleteJson($path.'/keys/'.$record->id)->assertOk();
    $this->withToken($first['plain_text_key'])->getJson('/api/v1/locations/provinces')->assertUnauthorized();
    $this->withToken($second['plain_text_key'])->getJson('/api/v1/locations/provinces')->assertOk();
    expect(DB::table('activity_log')->pluck('properties')->implode(' '))->not->toContain($first['plain_text_key'], $record->key_hash);
});

it('requires admin permission for all key management operations', function () {
    $this->actingAs(User::factory()->create(), 'web');
    $id = $this->integration->id;
    $key = ($this->issue)()['key']->id;
    foreach ([['GET', ''], ['GET', '/scopes'], ['POST', ''], ['GET', "/{$id}"], ['PATCH', "/{$id}"], ['POST', "/{$id}/keys"], ['DELETE', "/{$id}/keys/{$key}"]] as [$method, $suffix]) {
        $this->json($method, '/api/v1/integrations'.$suffix)->assertForbidden();
    }
});

it('validates scopes expiration and nested key ownership', function () {
    $this->actingAs($this->admin, 'web');
    $path = '/api/v1/integrations/'.$this->integration->id;
    foreach ([[], ['*'], ['pembandings:write'], ['locations:read', 'locations:read']] as $scopes) {
        $this->postJson($path.'/keys', ['name' => 'Key', 'scopes' => $scopes, 'expires_at' => now()->addDay()->toIso8601String()])->assertUnprocessable();
    }
    foreach ([null, now()->subDay(), now()->addYears(2)] as $expiry) {
        $this->postJson($path.'/keys', ['name' => 'Key', 'scopes' => ['locations:read'], 'expires_at' => $expiry?->toIso8601String()])->assertUnprocessable();
    }
    $key = ($this->issue)()['key'];
    $other = Integration::create(['name' => 'Other']);
    $this->deleteJson('/api/v1/integrations/'.$other->id.'/keys/'.$key->id)->assertNotFound();
    expect($key->fresh()->revoked_at)->toBeNull();
    $this->patchJson($path, ['is_active' => false])->assertOk();
    $this->postJson($path.'/keys', ['name' => 'Key', 'scopes' => ['locations:read'], 'expires_at' => now()->addDay()->toIso8601String()])->assertStatus(409);
});

it('records safe telemetry including validation failures without logging payload or key', function () {
    $issued = ($this->issue)();
    $logger = new class extends NullLogger
    {
        public array $records = [];

        public function log($level, string|Stringable $message, array $context = []): void
        {
            $this->records[] = $context;
        }
    };
    Log::extend('integration_test', fn () => $logger);
    config()->set('logging.channels.integration', ['driver' => 'integration_test']);
    Log::forgetChannel('integration');
    $this->withToken($issued['plain_text_key'])->postJson('/api/v1/pembandings/similar', [])->assertUnprocessable();
    expect($logger->records)->toHaveCount(1)
        ->and($logger->records[0]['status'])->toBe(422)
        ->and($logger->records[0]['key_id'])->toBe($issued['key']->id)
        ->and($logger->records[0]['route'])->toBe('api/v1/pembandings/similar')
        ->and(array_keys($logger->records[0]))->toBe(['integration_id', 'key_id', 'method', 'route', 'status', 'duration_ms']);
});
