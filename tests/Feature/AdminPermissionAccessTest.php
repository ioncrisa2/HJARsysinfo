<?php

use App\Models\Pembanding;
use App\Models\PembandingDeleteRequest;
use App\Models\User;
use Database\Seeders\AdminPanelPermissionSeeder;
use Database\Seeders\PembandingAccessRoleSeeder;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->seed(PembandingAccessRoleSeeder::class);
});

function adminPermissionUser(array $permissions): User
{
    $user = User::factory()->create(['deactivated_at' => null]);

    collect($permissions)
        ->each(fn (string $permission) => Permission::findOrCreate($permission, 'web'));

    $user->givePermissionTo($permissions);

    return $user;
}

it('requires admin access permission before any admin page can be opened', function () {
    $user = adminPermissionUser(['view_any_user']);

    $this->actingAs($user)
        ->get('/admin/users')
        ->assertForbidden();
});

it('allows viewing users without allowing user deletion', function () {
    $user = adminPermissionUser(['can_access_admin', 'view_any_user']);
    $target = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin/users');

    $response->assertOk();

    $props = $response->viewData('page')['props'];

    expect($response->viewData('page')['component'])->toBe('Admin/Users/Index')
        ->and($props['can'])->toMatchArray([
            'create' => false,
            'update' => false,
            'delete' => false,
            'deleteAny' => false,
        ]);

    $this->actingAs($user)
        ->delete("/admin/users/{$target->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('users', ['id' => $target->id]);
});

it('filters admin menu from backend permissions', function () {
    $user = adminPermissionUser([
        'can_access_admin',
        'view_any_user',
        'view_export',
    ]);

    $response = $this->actingAs($user)->get('/admin/users');

    $response->assertOk();

    $menuLabels = collect($response->viewData('page')['props']['adminMenu'])
        ->pluck('items')
        ->flatten(1)
        ->pluck('label')
        ->all();

    expect($menuLabels)
        ->toContain('Users', 'Export Data')
        ->not->toContain('Settings', 'System Backup', 'Access Control');
});

it('shows pending delete request alert on admin dashboard only with moderation access', function () {
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

    $moderator = adminPermissionUser([
        'can_access_admin',
        'view_admin_dashboard',
        'view_moderation',
    ]);

    $response = $this->actingAs($moderator)->get('/admin');

    $response->assertOk();

    $props = $response->viewData('page')['props'];

    expect($response->viewData('page')['component'])->toBe('Admin/Dashboard')
        ->and($props['deleteRequestAlert'])->toMatchArray([
            'count' => 1,
            'href' => route('admin.moderation.index', ['tab' => 'requests']),
        ]);

    $dashboardOnly = adminPermissionUser([
        'can_access_admin',
        'view_admin_dashboard',
    ]);

    $response = $this->actingAs($dashboardOnly)->get('/admin');

    $response->assertOk();

    expect($response->viewData('page')['props']['deleteRequestAlert'])->toBeNull();
});

it('allows user deletion only with delete user permission', function () {
    $user = adminPermissionUser(['can_access_admin', 'delete_user']);
    $target = User::factory()->create();

    $this->actingAs($user)
        ->delete("/admin/users/{$target->id}")
        ->assertRedirect('/admin/users');

    $this->assertDatabaseMissing('users', ['id' => $target->id]);
});

it('migrates legacy admin panel permission to canonical admin access permission', function () {
    $legacy = Permission::findOrCreate('can_access_admin_panel', 'web');
    $user = User::factory()->create(['deactivated_at' => null]);
    $user->givePermissionTo($legacy);

    $this->seed(AdminPanelPermissionSeeder::class);
    $user->refresh();

    expect($user->can('can_access_admin'))->toBeTrue()
        ->and(Permission::query()->where('name', 'can_access_admin_panel')->exists())->toBeFalse();
});

it('blocks sensitive admin actions without their action permission', function (string $method, string $uri) {
    $user = adminPermissionUser([
        'can_access_admin',
        'view_settings',
        'view_backup',
        'view_export',
        'view_moderation',
        'view_master_data',
        'view_geo_data',
    ]);

    $this->actingAs($user)
        ->call($method, $uri)
        ->assertForbidden();
})->with([
    ['POST', '/admin/settings'],
    ['POST', '/admin/settings/clear-cache'],
    ['POST', '/admin/backup/database'],
    ['POST', '/admin/backup/uploads'],
    ['GET', '/admin/export/download?format=excel'],
    ['POST', '/admin/moderation/approve/1'],
    ['POST', '/admin/moderation/reject/1'],
    ['POST', '/admin/moderation/restore/1'],
    ['DELETE', '/admin/moderation/force-delete/1'],
    ['POST', '/admin/master-data/jenis-objek'],
    ['POST', '/admin/master-data/jenis-objek/reorder'],
    ['DELETE', '/admin/master-data/jenis-objek/1'],
    ['POST', '/admin/geo/provinces'],
    ['DELETE', '/admin/geo/provinces/11'],
]);
