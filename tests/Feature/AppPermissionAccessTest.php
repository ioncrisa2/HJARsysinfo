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
        ->flatMap(fn (array $section): array => collect($section['items'])
            ->flatMap(fn (array $item): array => [
                $item['label'],
                ...collect($item['children'] ?? [])->pluck('label')->all(),
            ])
            ->all())
        ->all();

    expect($menuLabels)
        ->toContain('Dashboard', 'Users', 'Export Data')
        ->not->toContain('Pengaturan', 'Backup Sistem', 'Access Control');
});

it('builds the shared menu for every primary role', function (string $role, array $visible, array $hidden) {
    $response = $this->actingAs(appRoleUser($role))->get('/app')->assertOk();
    $labels = collect($response->viewData('page')['props']['appMenu'])
        ->flatMap(fn (array $section): array => collect($section['items'])
            ->flatMap(fn (array $item): array => [
                $item['label'],
                ...collect($item['children'] ?? [])->pluck('label')->all(),
            ])
            ->all())
        ->all();

    expect($labels)->toContain(...$visible);
    foreach ($hidden as $label) {
        expect($labels)->not->toContain($label);
    }
})->with([
    'super admin' => ['super_admin', ['Dashboard', 'Users', 'Daftar Data', 'Bulk Import', 'Pengaturan'], []],
    'pimpinan' => ['pimpinan', ['Dashboard', 'Bank Data', 'Daftar Data'], ['Users', 'Bulk Import', 'Pengaturan']],
    'data contributor' => ['data_contributor', ['Dashboard', 'Bank Data', 'Daftar Data'], ['Users', 'Bulk Import', 'Pengaturan']],
    'surveyor' => ['surveyor', ['Dashboard', 'Bank Data', 'Daftar Data'], ['Users', 'Bulk Import', 'Pengaturan']],
    'bulk import' => ['bulk_import', ['Dashboard', 'Bank Data', 'Bulk Import'], ['Users', 'Daftar Data', 'Pengaturan']],
]);

it('shares narrow frontend capabilities instead of raw permissions or panel flags', function () {
    $user = appPermissionUser(['view_search']);
    $props = $this->actingAs($user)->get('/app')->assertOk()->viewData('page')['props'];

    expect($props['auth'])
        ->toHaveKey('user')
        ->toHaveKey('can.search', true)
        ->not->toHaveKeys(['permissions', 'is_super_admin', 'can_bulk_import'])
        ->and(collect($props['appMenu'])->pluck('items')->flatten(1)->pluck('label')->all())
        ->not->toContain('Pencarian');
});

it('shares pembanding action capabilities with its index and dashboard pages', function () {
    $user = appPermissionUser([
        'view_any_data::pembanding',
        'create_data::pembanding',
        'export_data::pembanding',
    ]);

    $indexProps = $this->actingAs($user)->get('/app/pembanding')->assertOk()->viewData('page')['props'];
    expect($indexProps['can'])->toBe([
        'create' => true,
        'export' => true,
    ]);

    $dashboardProps = $this->actingAs($user)->get('/app')->assertOk()->viewData('page')['props'];
    expect($dashboardProps['can'])->toBe([
        'viewData' => true,
        'createData' => true,
    ]);
});

it('does not expose pembanding actions without their permissions', function () {
    $user = appPermissionUser(['view_any_data::pembanding']);

    $props = $this->actingAs($user)->get('/app/pembanding')->assertOk()->viewData('page')['props'];

    expect($props['can'])->toBe([
        'create' => false,
        'export' => false,
    ]);
});

it('shows bank data children according to permissions', function () {
    $user = appPermissionUser([
        'view_any_data::pembanding',
        'bulk_import_data::pembanding',
        'view_moderation',
        'view_export',
    ]);

    $response = $this->actingAs($user)->get('/app')->assertOk();
    $operations = collect($response->viewData('page')['props']['appMenu'])
        ->firstWhere('label', 'Operasional Data');
    $bankData = collect($operations['items'])->firstWhere('label', 'Bank Data');

    expect(collect($bankData['children'])->pluck('label')->all())
        ->toBe(['Daftar Data', 'Bulk Import', 'Moderasi Data', 'Export Data'])
        ->and(collect($bankData['children'])->pluck('href')->all())
        ->toBe(['/app/pembanding', '/app/pembanding-imports', '/app/moderation', '/app/export']);
});

it('orders sidebar sections by daily workflow', function () {
    $user = appPermissionUser([
        'view_any_data::pembanding',
        'view_master_data',
        'view_any_user',
        'view_backup',
    ]);

    $response = $this->actingAs($user)->get('/app')->assertOk();

    expect(collect($response->viewData('page')['props']['appMenu'])->pluck('label')->all())
        ->toBe(['Ringkasan', 'Operasional Data', 'Referensi Data', 'User & Akses', 'Sistem']);
});

it('separates master data and geo location navigation by permission', function (array $permissions, array $visible, array $hidden) {
    $response = $this->actingAs(appPermissionUser($permissions))->get('/app')->assertOk();
    $labels = collect($response->viewData('page')['props']['appMenu'])
        ->flatMap(fn (array $section): array => collect($section['items'])->pluck('label')->all())
        ->all();

    expect($labels)->toContain(...$visible);
    foreach ($hidden as $label) {
        expect($labels)->not->toContain($label);
    }
})->with([
    'master data only' => [['view_master_data'], ['Master Data'], ['Geo Location']],
    'geo location only' => [['view_geo_data'], ['Geo Location'], ['Master Data']],
]);

it('builds master data as one parent with overview and every registered category', function () {
    $response = $this->actingAs(appPermissionUser(['view_master_data']))->get('/app')->assertOk();
    $references = collect($response->viewData('page')['props']['appMenu'])->firstWhere('label', 'Referensi Data');
    $masterData = collect($references['items'])->firstWhere('label', 'Master Data');

    expect($masterData['href'])->toBe('/app/master-data')
        ->and($masterData['children'])->toHaveCount(10)
        ->and(collect($masterData['children'])->pluck('label')->all())->toBe([
            'Ringkasan',
            'Jenis Listing',
            'Jenis Objek',
            'Status Pemberi Informasi',
            'Bentuk Tanah',
            'Kondisi Tanah',
            'Posisi Tanah',
            'Topografi',
            'Dokumen Tanah',
            'Peruntukan',
        ]);
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
    ['PATCH', '/app/master-data/dictionaries/jenis-objek/1/status'],
    ['DELETE', '/app/master-data/dictionaries/jenis-objek/1'],
    ['POST', '/app/geo/provinces'],
    ['DELETE', '/app/geo/provinces/11'],
]);
