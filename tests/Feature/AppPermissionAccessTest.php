<?php

use App\Models\Pembanding;
use App\Models\PembandingDeleteRequest;
use App\Models\User;
use Database\Seeders\AppAccessPermissionSeeder;
use Database\Seeders\PembandingAccessRoleSeeder;
use Spatie\Permission\Models\Permission;

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

it('protects each application feature with its own permission', function () {
    $user = appPermissionUser([]);

    $this->actingAs($user)->get('/app')->assertOk();
    $this->actingAs($user)->get('/app/users')->assertForbidden();
    $this->actingAs($user)->get('/app/settings')->assertForbidden();
    $this->actingAs($user)->get('/app/pembanding')->assertForbidden();
});

it('allows viewing users without allowing user deletion', function () {
    $user = appPermissionUser(['view_any_user']);
    $target = User::factory()->create();

    $response = $this->actingAs($user)->get('/app/users')->assertOk();
    $props = $response->viewData('page')['props'];

    expect($response->viewData('page')['component'])->toBe('Users/Index')
        ->and($props['can'])->toMatchArray([
            'create' => false,
            'update' => false,
            'delete' => false,
            'deleteAny' => false,
        ]);

    $this->actingAs($user)->delete("/app/users/{$target->id}")->assertForbidden();
    $this->assertDatabaseHas('users', ['id' => $target->id]);
});

it('filters the shared application menu from backend permissions', function () {
    $user = appPermissionUser(['view_any_user', 'view_export']);

    $response = $this->actingAs($user)->get('/app/users')->assertOk();
    $menuLabels = collect($response->viewData('page')['props']['appMenu'])
        ->pluck('items')
        ->flatten(1)
        ->pluck('label')
        ->all();

    expect($menuLabels)
        ->toContain('Dashboard', 'Users', 'Export Data')
        ->not->toContain('Pengaturan', 'Backup Sistem', 'Access Control');
});

it('shows bank data children according to permissions', function () {
    $user = appPermissionUser([
        'view_any_data::pembanding',
        'bulk_import_data::pembanding',
    ]);

    $response = $this->actingAs($user)->get('/app')->assertOk();
    $operations = collect($response->viewData('page')['props']['appMenu'])
        ->firstWhere('label', 'Operasional Data');
    $bankData = collect($operations['items'])->firstWhere('label', 'Bank Data');

    expect(collect($bankData['children'])->pluck('label')->all())
        ->toBe(['Daftar Data', 'Bulk Import'])
        ->and(collect($bankData['children'])->pluck('href')->all())
        ->toBe(['/app/pembanding', '/app/pembanding-imports']);
});

it('shows pending delete requests only to users with moderation access', function () {
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
    $response = $this->actingAs($moderator)->get('/app')->assertOk();

    expect($response->viewData('page')['component'])->toBe('Dashboard')
        ->and($response->viewData('page')['props']['deleteRequestAlert'])->toMatchArray([
            'count' => 1,
            'href' => route('app.moderation.index', ['tab' => 'requests']),
        ]);

    $regular = appPermissionUser([]);
    $response = $this->actingAs($regular)->get('/app')->assertOk();
    expect($response->viewData('page')['props']['deleteRequestAlert'])->toBeNull();
});

it('provides requester and reviewer identities to the moderation page', function () {
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
    $response = $this->actingAs($moderator)->get('/app/moderation?tab=requests')->assertOk();
    $page = $response->viewData('page');
    $deleteRequest = $page['props']['requestsPaginator']['data'][0];

    expect($page['component'])->toBe('Moderation/Index')
        ->and($deleteRequest['requested_by']['name'])->toBe('Surveyor Requester')
        ->and($deleteRequest['reviewed_by']['name'])->toBe('System Reviewer');
});

it('filters dashboard widgets from widget permissions', function () {
    $user = appPermissionUser(['widget_StatsOverview']);
    $response = $this->actingAs($user)->get('/app')->assertOk();
    $props = $response->viewData('page')['props'];

    expect($response->viewData('page')['component'])->toBe('Dashboard')
        ->and($props['canWidgets']['statsOverview'])->toBeTrue()
        ->and($props['canWidgets']['dataEntryTrendChart'])->toBeFalse()
        ->and($props['canWidgets']['map'])->toBeFalse()
        ->and($props['monthlyData'])->toHaveCount(0)
        ->and($props['mapPoints'])->toHaveCount(0);
});

it('allows user deletion only with delete user permission', function () {
    $user = appPermissionUser(['delete_user']);
    $target = User::factory()->create();

    $this->actingAs($user)
        ->delete("/app/users/{$target->id}")
        ->assertRedirect('/app/users');

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

    $this->actingAs($user)->call($method, $uri)->assertForbidden();
})->with([
    ['POST', '/app/settings'],
    ['POST', '/app/settings/clear-cache'],
    ['POST', '/app/backup/database'],
    ['POST', '/app/backup/uploads'],
    ['GET', '/app/export/download?format=excel'],
    ['POST', '/app/moderation/approve/1'],
    ['POST', '/app/moderation/reject/1'],
    ['POST', '/app/moderation/restore/1'],
    ['DELETE', '/app/moderation/force-delete/1'],
    ['POST', '/app/master-data/dictionaries/jenis-objek'],
    ['POST', '/app/master-data/dictionaries/jenis-objek/reorder'],
    ['DELETE', '/app/master-data/dictionaries/jenis-objek/1'],
    ['POST', '/app/geo/provinces'],
    ['DELETE', '/app/geo/provinces/11'],
]);
