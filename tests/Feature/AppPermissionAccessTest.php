<?php

use App\Models\Pembanding;
use App\Models\PembandingDeleteRequest;
use App\Models\User;
use Database\Seeders\AppAccessPermissionSeeder;
use Database\Seeders\PembandingAccessRoleSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(PembandingAccessRoleSeeder::class);
});

function appPermissionUser(array $permissions): User
{
    $user = User::factory()->create(['deactivated_at' => null]);

    collect($permissions)
        ->each(fn (string $permission) => Permission::findOrCreate($permission, 'web'));

    $user->givePermissionTo($permissions);

    return $user;
}

function appRoleUser(string $role): User
{
    $user = User::factory()->create(['deactivated_at' => null]);
    $user->assignRole(Role::findByName($role, 'web'));

    return $user;
}

it('protects each application feature with its own permission', function () {
    $user = appPermissionUser([]);

    $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard')->assertOk();
    $this->actingAs($user, 'sanctum')->getJson('/api/v1/users')->assertForbidden();
    $this->actingAs($user, 'sanctum')->getJson('/api/v1/settings')->assertForbidden();
    $this->actingAs($user, 'sanctum')->getJson('/api/v1/pembandings')->assertForbidden();
});

it('allows viewing users without allowing user deletion', function () {
    $user = appPermissionUser(['view_any_user']);
    $target = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/users')->assertOk();

    $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/users/{$target->id}")->assertForbidden();
    $this->assertDatabaseHas('users', ['id' => $target->id]);
});

it('shares pembanding action capabilities in form-options and dashboard', function () {
    $user = appPermissionUser([
        'view_any_data::pembanding',
        'create_data::pembanding',
        'export_data::pembanding',
    ]);

    $dashboardResponse = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard')->assertOk();
    expect($dashboardResponse->json('data.can.create_data'))->toBeTrue();
});

it('shows pending delete requests alert in dashboard only to users with moderation access', function () {
    $requester = User::factory()->create();
    $pembanding = Pembanding::create([
        'nama_pemberi_informasi' => 'Pemilik Data',
        'nomer_telepon_pemberi_informasi' => '081234567890',
        'alamat_data' => 'Jl. Audit Permission No. 1',
        'latitude' => -6.200000,
        'longitude' => 106.816666,
        'created_by' => $requester->id,
    ]);
    PembandingDeleteRequest::create([
        'pembanding_id' => $pembanding->id,
        'requested_by_id' => $requester->id,
        'reason' => 'Data perlu dihapus',
        'status' => PembandingDeleteRequest::STATUS_PENDING,
    ]);

    $moderator = appPermissionUser(['view_moderation']);
    $response = $this->actingAs($moderator, 'sanctum')->getJson('/api/v1/dashboard')->assertOk();

    expect($response->json('data.delete_request_alert'))->toMatchArray([
        'count' => 1,
    ]);

    $regular = appPermissionUser([]);
    $response = $this->actingAs($regular, 'sanctum')->getJson('/api/v1/dashboard')->assertOk();
    expect($response->json('data.delete_request_alert'))->toBeNull();
});

it('provides requester and reviewer identities to the moderation api', function () {
    $requester = User::factory()->create(['name' => 'Surveyor Requester']);
    $reviewer = User::factory()->create(['name' => 'System Reviewer']);
    $pembanding = Pembanding::create([
        'nama_pemberi_informasi' => 'Pemilik Data',
        'nomer_telepon_pemberi_informasi' => '081234567890',
        'alamat_data' => 'Jl. Moderation Contract No. 1',
        'latitude' => -6.200000,
        'longitude' => 106.816666,
        'created_by' => $requester->id,
    ]);
    PembandingDeleteRequest::create([
        'pembanding_id' => $pembanding->id,
        'requested_by_id' => $requester->id,
        'reason' => 'Data duplikat',
        'status' => PembandingDeleteRequest::STATUS_APPROVED,
        'reviewed_by_id' => $reviewer->id,
        'reviewed_at' => now(),
    ]);

    $moderator = appPermissionUser(['view_moderation']);
    $response = $this->actingAs($moderator, 'sanctum')->getJson('/api/v1/moderation?tab=requests')->assertOk();
    $deleteRequest = $response->json('data.0');

    expect($deleteRequest['requested_by']['name'])->toBe('Surveyor Requester')
        ->and($deleteRequest['reviewed_by']['name'])->toBe('System Reviewer');
});

it('filters dashboard widgets from widget permissions', function () {
    $user = appPermissionUser(['widget_StatsOverview']);
    $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard')->assertOk();

    expect($response->json('data.can_widgets.statsOverview'))->toBeTrue()
        ->and($response->json('data.can_widgets.dataEntryTrendChart'))->toBeFalse()
        ->and($response->json('data.can_widgets.map'))->toBeFalse()
        ->and($response->json('data.monthly_data'))->toHaveCount(0)
        ->and($response->json('data.map_points'))->toHaveCount(0);
});

it('allows user deletion only with delete user permission', function () {
    $user = appPermissionUser(['delete_user']);
    $target = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/users/{$target->id}")
        ->assertOk()
        ->assertJsonPath('status', 'success');

    $this->assertDatabaseMissing('users', ['id' => $target->id]);
});

it('migrates legacy panel permissions to canonical application permissions', function () {
    $legacySearch = Permission::findOrCreate('view_admin_search', 'web');
    $legacyAccess = Permission::findOrCreate('can_access_admin', 'web');
    $user = User::factory()->create(['deactivated_at' => null]);
    $user->givePermissionTo([$legacySearch, $legacyAccess]);

    $this->seed(AppAccessPermissionSeeder::class);
    $user->refresh();

    expect($user->can('view_search'))->toBeTrue()
        ->and(Permission::query()->whereIn('name', ['view_admin_search', 'can_access_admin'])->exists())->toBeFalse();
});

it('blocks sensitive actions without their action permission', function (string $method, string $uri) {
    $user = appPermissionUser([
        'view_settings',
        'view_backup',
        'view_export',
        'view_moderation',
        'view_master_data',
        'view_geo_data',
    ]);

    $this->actingAs($user, 'sanctum')->json($method, $uri)->assertForbidden();
})->with([
    ['POST', '/api/v1/settings'],
    ['POST', '/api/v1/settings/clear-cache'],
    ['POST', '/api/v1/backup/artifacts'],
    ['POST', '/api/v1/backup/imports'],
    ['GET', '/api/v1/backup/artifacts/unknown/download'],
    ['POST', '/api/v1/backup/artifacts/unknown/verify'],
    ['DELETE', '/api/v1/backup/artifacts/unknown'],
    ['POST', '/api/v1/backup/artifacts/unknown/restore-uploads'],
    ['GET', '/api/v1/exports/download?format=excel'],
    ['POST', '/api/v1/moderation/delete-requests/1/approve'],
    ['POST', '/api/v1/moderation/delete-requests/1/reject'],
    ['POST', '/api/v1/moderation/pembandings/1/restore'],
    ['DELETE', '/api/v1/moderation/pembandings/1'],
    ['POST', '/api/v1/dictionaries/jenis-objek'],
    ['POST', '/api/v1/dictionaries/jenis-objek/reorder'],
    ['PATCH', '/api/v1/dictionaries/jenis-objek/1/status'],
    ['DELETE', '/api/v1/dictionaries/jenis-objek/1'],
    ['POST', '/api/v1/geo/provinces'],
    ['DELETE', '/api/v1/geo/provinces/11'],
]);
