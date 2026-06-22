<?php

namespace Tests\Feature;

use App\Models\Province;
use App\Models\User;
use Database\Seeders\MasterDataPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Permission;

class MasterDataApiTest extends TestCase
{
    use RefreshDatabase;

    private function signIn()
    {
        $user = User::factory()->create([
            'deactivated_at' => null,
        ]);

        $permissions = [
            'view_master_data',
            'create_master_data',
            'update_master_data',
            'delete_master_data',
            'reorder_master_data',
            'view_geo_data',
            'create_geo_data',
            'update_geo_data',
            'delete_geo_data',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo($permissions);

        $this->actingAs($user);

        // CSRF not needed for feature tests here
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_can_crud_dictionary_items()
    {
        $this->signIn();

        // Create #1 (sort order should auto-append to bottom -> 1)
        $first = $this->postJson('/home/master-data/dictionaries/jenis-objek', [
            'name' => 'Gudang Besar',
            'sort_order' => 99, // ignored by backend
            'is_active' => true,
        ]);
        $first->assertCreated()->assertJsonFragment(['slug' => 'gudang_besar']);
        $firstId = $first->json('id');

        // Create #2 (auto-append -> 2)
        $second = $this->postJson('/home/master-data/dictionaries/jenis-objek', [
            'name' => 'Rumah Contoh',
            'sort_order' => 0, // ignored by backend
            'is_active' => true,
        ]);
        $second->assertCreated()->assertJsonFragment(['slug' => 'rumah_contoh']);
        $secondId = $second->json('id');

        $this->assertDatabaseHas('master_jenis_objek', ['id' => $firstId, 'sort_order' => 1]);
        $this->assertDatabaseHas('master_jenis_objek', ['id' => $secondId, 'sort_order' => 2]);

        // Update item
        $update = $this->putJson("/home/master-data/dictionaries/jenis-objek/{$firstId}", [
            'name' => 'Gudang Pelabuhan',
            'is_active' => false,
        ]);
        $update->assertOk()->assertJsonFragment(['slug' => 'gudang_pelabuhan', 'is_active' => false]);

        // Reorder (second becomes first)
        $this->postJson('/home/master-data/dictionaries/jenis-objek/reorder', [
            'ids' => [$secondId, $firstId],
        ])->assertOk()->assertJsonFragment(['success' => true]);

        $this->assertDatabaseHas('master_jenis_objek', ['id' => $secondId, 'sort_order' => 1]);
        $this->assertDatabaseHas('master_jenis_objek', ['id' => $firstId, 'sort_order' => 2]);

        // List contains update
        $this->getJson('/home/master-data/dictionaries/jenis-objek')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Gudang Pelabuhan']);

        // Delete
        $this->deleteJson("/home/master-data/dictionaries/jenis-objek/{$firstId}")
            ->assertOk();

        $this->getJson('/home/master-data/dictionaries/jenis-objek')
            ->assertOk()
            ->assertJsonMissing(['id' => $firstId]);
    }

    public function test_location_ids_follow_bps_and_are_uppercase()
    {
        $this->signIn();

        // Province manual ID
        $this->postJson('/home/master-data/locations/provinces', [
            'id' => '99',
            'name' => 'prov test',
        ])->assertCreated();

        $this->assertDatabaseHas('provinces', [
            'id' => '99',
            'name' => 'PROV TEST',
        ]);

        // Regency generated (should be 9901)
        $reg = $this->postJson('/home/master-data/locations/regencies', [
            'province_id' => '99',
            'name' => 'kota uji',
        ])->assertCreated();

        $regId = $reg->json('id');
        $this->assertEquals('9901', $regId);
        $this->assertDatabaseHas('regencies', ['id' => '9901', 'name' => 'KOTA UJI']);

        // District generated (should be 9901001)
        $dist = $this->postJson('/home/master-data/locations/districts', [
            'regency_id' => $regId,
            'name' => 'kecamatan uji',
        ])->assertCreated();
        $distId = $dist->json('id');
        $this->assertEquals('9901001', $distId);
        $this->assertDatabaseHas('districts', ['id' => $distId, 'name' => 'KECAMATAN UJI']);

        // Village generated (should be 9901001001)
        $vill = $this->postJson('/home/master-data/locations/villages', [
            'district_id' => $distId,
            'name' => 'desa uji',
        ])->assertCreated();
        $villId = $vill->json('id');
        $this->assertEquals('9901001001', $villId);
        $this->assertDatabaseHas('villages', ['id' => $villId, 'name' => 'DESA UJI']);

        // Filter regency by province
        $this->getJson('/home/master-data/locations/regencies?province_id=99')
            ->assertOk()
            ->assertJsonFragment(['id' => $regId]);
    }

    public function test_master_data_write_routes_require_granular_permissions()
    {
        $user = User::factory()->create(['deactivated_at' => null]);
        Permission::findOrCreate('view_master_data', 'web');
        $user->givePermissionTo('view_master_data');

        $this->actingAs($user);
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->getJson('/home/master-data/dictionaries/jenis-objek')
            ->assertOk();

        $this->postJson('/home/master-data/dictionaries/jenis-objek', [
            'name' => 'Tidak Boleh',
            'is_active' => true,
        ])->assertForbidden();
    }

    public function test_legacy_manage_master_data_permission_is_migrated_to_granular_permissions()
    {
        $user = User::factory()->create(['deactivated_at' => null]);
        $legacy = Permission::findOrCreate('manage_master_data', 'web');
        $user->givePermissionTo($legacy);

        $this->seed(MasterDataPermissionSeeder::class);
        $user->refresh();

        $this->assertTrue($user->can('view_master_data'));
        $this->assertTrue($user->can('create_master_data'));
        $this->assertTrue($user->can('view_geo_data'));
        $this->assertFalse(Permission::query()->where('name', 'manage_master_data')->exists());
    }
}
