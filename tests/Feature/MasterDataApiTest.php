<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\JenisObjek;
use App\Models\Pembanding;
use App\Models\Province;
use App\Models\User;
use App\Supports\DictionaryTypeMap;
use Database\Seeders\MasterDataPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
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

        $this->actingAs($user);

        // CSRF not needed for feature tests here
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_can_crud_dictionary_items()
    {
        $this->signIn();

        // Create #1 (sort order should auto-append to bottom -> 1)
        $first = $this->postJson('/app/master-data/dictionaries/jenis-objek', [
            'name' => 'Gudang Besar',
            'sort_order' => 99, // ignored by backend
            'is_active' => true,
        ]);
        $first->assertCreated()->assertJsonFragment(['slug' => 'gudang_besar']);
        $firstId = $first->json('id');

        // Create #2 (auto-append -> 2)
        $second = $this->postJson('/app/master-data/dictionaries/jenis-objek', [
            'name' => 'Rumah Contoh',
            'sort_order' => 0, // ignored by backend
            'is_active' => true,
        ]);
        $second->assertCreated()->assertJsonFragment(['slug' => 'rumah_contoh']);
        $secondId = $second->json('id');

        $this->assertDatabaseHas('master_jenis_objek', ['id' => $firstId, 'sort_order' => 1]);
        $this->assertDatabaseHas('master_jenis_objek', ['id' => $secondId, 'sort_order' => 2]);

        // Update item
        $update = $this->putJson("/app/master-data/dictionaries/jenis-objek/{$firstId}", [
            'name' => 'Gudang Pelabuhan',
        ]);
        $update->assertOk()->assertJsonFragment(['slug' => 'gudang_pelabuhan']);

        $this->patchJson("/app/master-data/dictionaries/jenis-objek/{$firstId}/status", [
            'is_active' => false,
        ])->assertOk()->assertJsonFragment(['is_active' => false]);

        // Reorder (second becomes first)
        $this->postJson('/app/master-data/dictionaries/jenis-objek/reorder', [
            'ids' => [$secondId, $firstId],
        ])->assertOk()->assertJsonFragment(['success' => true]);

        $this->assertDatabaseHas('master_jenis_objek', ['id' => $secondId, 'sort_order' => 1]);
        $this->assertDatabaseHas('master_jenis_objek', ['id' => $firstId, 'sort_order' => 2]);

        // List contains update
        $this->getJson('/app/master-data/dictionaries/jenis-objek')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Gudang Pelabuhan']);

        // Delete
        $this->deleteJson("/app/master-data/dictionaries/jenis-objek/{$firstId}")
            ->assertOk();

        $this->getJson('/app/master-data/dictionaries/jenis-objek')
            ->assertOk()
            ->assertJsonMissing(['id' => $firstId]);
    }

    public function test_geo_location_ids_follow_bps_and_are_uppercase()
    {
        $this->signIn();

        // Province manual ID
        $this->post('/app/geo/provinces', [
            'id' => '99',
            'name' => 'prov test',
        ])->assertRedirect('/app/geo/provinces');

        $this->assertDatabaseHas('provinces', [
            'id' => '99',
            'name' => 'PROV TEST',
        ]);

        // Regency generated (should be 9901)
        $this->post('/app/geo/regencies', [
            'province_id' => '99',
            'name' => 'kota uji',
        ])->assertRedirect('/app/geo/regencies');

        $regId = '9901';
        $this->assertEquals('9901', $regId);
        $this->assertDatabaseHas('regencies', ['id' => '9901', 'name' => 'KOTA UJI']);

        // District generated (should be 9901001)
        $this->post('/app/geo/districts', [
            'regency_id' => $regId,
            'name' => 'kecamatan uji',
        ])->assertRedirect('/app/geo/districts');
        $distId = '9901001';
        $this->assertEquals('9901001', $distId);
        $this->assertDatabaseHas('districts', ['id' => $distId, 'name' => 'KECAMATAN UJI']);

        // Village generated (should be 9901001001)
        $this->post('/app/geo/villages', [
            'district_id' => $distId,
            'name' => 'desa uji',
        ])->assertRedirect('/app/geo/villages');
        $villId = '9901001001';
        $this->assertEquals('9901001001', $villId);
        $this->assertDatabaseHas('villages', ['id' => $villId, 'name' => 'DESA UJI']);

        // Filter regency by province
        $response = $this->get('/app/geo/regencies?province_id=99')->assertOk();
        $records = collect($response->viewData('page')['props']['records']['data']);

        $this->assertTrue($records->contains(fn (array $record): bool => $record['id'] === $regId));

        $this->get('/app/master-data/locations/regencies?province_id=99')->assertNotFound();
    }

    public function test_master_data_write_routes_require_granular_permissions()
    {
        $user = User::factory()->create(['deactivated_at' => null]);
        Permission::findOrCreate('view_master_data', 'web');
        $user->givePermissionTo('view_master_data');

        $this->actingAs($user);
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->getJson('/app/master-data/dictionaries/jenis-objek')
            ->assertOk();

        $this->postJson('/app/master-data/dictionaries/jenis-objek', [
            'name' => 'Tidak Boleh',
            'is_active' => true,
        ])->assertForbidden();
    }

    public function test_master_data_overview_and_every_registered_child_page_render()
    {
        $this->signIn();

        $this->get('/app/master-data')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('MasterData/Index')
                ->has('categories', 9)
                ->where('categories.0.type', 'jenis-listing')
                ->has('categories.0.stats.total')
            );

        foreach (DictionaryTypeMap::publicDefinitions() as $definition) {
            $this->get("/app/master-data/{$definition['type']}")
                ->assertOk()
                ->assertInertia(fn (AssertableInertia $page) => $page
                    ->component('MasterData/Show')
                    ->where('category.type', $definition['type'])
                    ->where('category.label', $definition['label'])
                    ->has('items')
                    ->has('can.create')
                    ->has('can.update')
                    ->has('can.update_status')
                    ->has('can.delete')
                    ->has('can.reorder')
                );
        }

        $this->get('/app/master-data/tidak-valid')->assertNotFound();
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

        $this->actingAs($user)->withoutMiddleware(VerifyCsrfToken::class);

        $this->patchJson("/app/master-data/dictionaries/jenis-objek/{$item->id}/status", [
            'is_active' => false,
        ])->assertOk();

        $this->assertDatabaseHas('master_jenis_objek', ['id' => $item->id, 'is_active' => false]);
        $this->putJson("/app/master-data/dictionaries/jenis-objek/{$item->id}", [
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

        $this->deleteJson("/app/master-data/dictionaries/jenis-objek/{$item->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('delete');

        $this->assertDatabaseHas('master_jenis_objek', ['id' => $item->id]);
        $this->assertDatabaseHas('data_pembanding', [
            'id' => $pembanding->id,
            'jenis_objek_id' => $item->id,
        ]);

        $pembanding->delete();

        $this->deleteJson("/app/master-data/dictionaries/jenis-objek/{$item->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('delete');

        $this->assertDatabaseHas('master_jenis_objek', ['id' => $item->id]);
    }

    public function test_jenis_listing_supports_its_extra_fields()
    {
        $this->signIn();

        $this->postJson('/app/master-data/dictionaries/jenis-listing', [
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
