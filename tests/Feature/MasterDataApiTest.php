<?php

namespace Tests\Feature;

use App\Models\JenisObjek;
use App\Models\Pembanding;
use App\Models\Province;
use App\Models\User;
use App\Supports\DictionaryTypeMap;
use Database\Seeders\MasterDataPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

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
            'update_master_data_status',
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

        $this->actingAs($user, 'sanctum');
    }

    public function test_can_crud_dictionary_items()
    {
        $this->signIn();

        // Create #1 (sort order should auto-append to bottom -> 1)
        $first = $this->postJson('/api/v1/dictionaries/jenis-objek', [
            'name' => 'Gudang Besar',
            'sort_order' => 99,
            'is_active' => true,
        ]);
        $first->assertCreated()->assertJsonFragment(['slug' => 'gudang_besar']);
        $firstId = $first->json('data.id') ?? $first->json('id');

        // Create #2 (auto-append -> 2)
        $second = $this->postJson('/api/v1/dictionaries/jenis-objek', [
            'name' => 'Rumah Contoh',
            'sort_order' => 0,
            'is_active' => true,
        ]);
        $second->assertCreated()->assertJsonFragment(['slug' => 'rumah_contoh']);
        $secondId = $second->json('data.id') ?? $second->json('id');

        $this->assertDatabaseHas('master_jenis_objek', ['id' => $firstId, 'sort_order' => 1]);
        $this->assertDatabaseHas('master_jenis_objek', ['id' => $secondId, 'sort_order' => 2]);

        // Update item
        $update = $this->putJson("/api/v1/dictionaries/jenis-objek/{$firstId}", [
            'name' => 'Gudang Pelabuhan',
        ]);
        $update->assertOk()->assertJsonFragment(['slug' => 'gudang_pelabuhan']);

        $this->patchJson("/api/v1/dictionaries/jenis-objek/{$firstId}/status", [
            'is_active' => false,
        ])->assertOk()->assertJsonFragment(['is_active' => false]);

        // Reorder (second becomes first)
        $this->postJson('/api/v1/dictionaries/jenis-objek/reorder', [
            'ids' => [$secondId, $firstId],
        ])->assertOk()->assertJsonFragment(['status' => 'success']);

        $this->assertDatabaseHas('master_jenis_objek', ['id' => $secondId, 'sort_order' => 1]);
        $this->assertDatabaseHas('master_jenis_objek', ['id' => $firstId, 'sort_order' => 2]);

        // List contains update
        $this->getJson('/api/v1/dictionaries/jenis-objek?active_only=0')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Gudang Pelabuhan']);

        // Delete
        $this->deleteJson("/api/v1/dictionaries/jenis-objek/{$firstId}")
            ->assertOk();

        $this->getJson('/api/v1/dictionaries/jenis-objek')
            ->assertOk()
            ->assertJsonMissing(['id' => $firstId]);
    }

    public function test_geo_location_ids_follow_bps_and_are_uppercase()
    {
        $this->signIn();

        // Province manual ID
        $this->postJson('/api/v1/geo/provinces', [
            'id' => '99',
            'name' => 'prov test',
        ])->assertCreated()->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('provinces', [
            'id' => '99',
            'name' => 'PROV TEST',
        ]);

        // Regency generated (should be 9901)
        $this->postJson('/api/v1/geo/regencies', [
            'province_id' => '99',
            'name' => 'kota uji',
        ])->assertCreated()->assertJsonPath('status', 'success');

        $regId = '9901';
        $this->assertEquals('9901', $regId);
        $this->assertDatabaseHas('regencies', ['id' => '9901', 'name' => 'KOTA UJI']);

        // District generated (should be 9901001)
        $this->postJson('/api/v1/geo/districts', [
            'regency_id' => $regId,
            'name' => 'kecamatan uji',
        ])->assertCreated()->assertJsonPath('status', 'success');
        $distId = '9901001';
        $this->assertEquals('9901001', $distId);
        $this->assertDatabaseHas('districts', ['id' => $distId, 'name' => 'KECAMATAN UJI']);

        // Village generated (should be 9901001001)
        $this->postJson('/api/v1/geo/villages', [
            'district_id' => $distId,
            'name' => 'desa uji',
        ])->assertCreated()->assertJsonPath('status', 'success');
        $villId = '9901001001';
        $this->assertEquals('9901001001', $villId);
        $this->assertDatabaseHas('villages', ['id' => $villId, 'name' => 'DESA UJI']);

        // Filter regency by province
        $response = $this->getJson('/api/v1/geo/regencies?province_id=99')->assertOk();
        $records = collect($response->json('data'));

        $this->assertTrue($records->contains(fn (array $record): bool => $record['id'] === $regId));
    }

    public function test_master_data_write_routes_require_granular_permissions()
    {
        $user = User::factory()->create(['deactivated_at' => null]);
        Permission::findOrCreate('view_master_data', 'web');
        $user->givePermissionTo('view_master_data');

        $this->actingAs($user, 'sanctum');

        $this->getJson('/api/v1/dictionaries/jenis-objek')
            ->assertOk();

        $this->postJson('/api/v1/dictionaries/jenis-objek', [
            'name' => 'Tidak Boleh',
            'is_active' => true,
        ])->assertForbidden();
    }

    public function test_master_data_definitions_and_every_registered_child_endpoint()
    {
        $this->signIn();

        $this->getJson('/api/v1/dictionaries')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['data' => [['type', 'label']]]);

        foreach (DictionaryTypeMap::publicDefinitions() as $definition) {
            $this->getJson("/api/v1/dictionaries/{$definition['type']}")
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonStructure(['data']);
        }

        $this->getJson('/api/v1/dictionaries/tidak-valid')->assertNotFound();
    }

    public function test_status_updates_use_their_own_permission()
    {
        $item = JenisObjek::query()->create([
            'name' => 'Gudang',
            'slug' => 'gudang',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $user = User::factory()->create(['deactivated_at' => null]);
        Permission::findOrCreate('view_master_data', 'web');
        Permission::findOrCreate('update_master_data_status', 'web');
        $user->givePermissionTo(['view_master_data', 'update_master_data_status']);

        $this->actingAs($user, 'sanctum');

        $this->patchJson("/api/v1/dictionaries/jenis-objek/{$item->id}/status", [
            'is_active' => false,
        ])->assertOk();

        $this->assertDatabaseHas('master_jenis_objek', ['id' => $item->id, 'is_active' => false]);
        $this->putJson("/api/v1/dictionaries/jenis-objek/{$item->id}", [
            'name' => 'Gudang Baru',
        ])->assertForbidden();
    }

    public function test_used_master_data_cannot_be_deleted_and_keeps_its_reference()
    {
        $this->signIn();
        $item = JenisObjek::query()->create([
            'name' => 'Ruko',
            'slug' => 'ruko',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $pembanding = Pembanding::query()->create([
            'nama_pemberi_informasi' => 'Penguji',
            'nomer_telepon_pemberi_informasi' => '08123456789',
            'alamat_data' => 'Jalan Pengujian',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'jenis_objek_id' => $item->id,
        ]);

        $this->deleteJson("/api/v1/dictionaries/jenis-objek/{$item->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('delete');

        $this->assertDatabaseHas('master_jenis_objek', ['id' => $item->id]);
        $this->assertDatabaseHas('data_pembanding', [
            'id' => $pembanding->id,
            'jenis_objek_id' => $item->id,
        ]);

        $pembanding->delete();

        $this->deleteJson("/api/v1/dictionaries/jenis-objek/{$item->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('delete');

        $this->assertDatabaseHas('master_jenis_objek', ['id' => $item->id]);
    }

    public function test_jenis_listing_supports_its_extra_fields()
    {
        $this->signIn();

        $this->postJson('/api/v1/dictionaries/jenis-listing', [
            'name' => 'Penawaran Khusus',
            'badge_color' => '#64748b',
            'marker_icon_url' => 'https://example.test/marker.svg',
        ])->assertCreated()->assertJsonFragment([
            'badge_color' => '#64748b',
            'marker_icon_url' => 'https://example.test/marker.svg',
        ]);

        $this->assertDatabaseHas('master_jenis_listing', [
            'slug' => 'penawaran_khusus',
            'badge_color' => '#64748b',
        ]);
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
