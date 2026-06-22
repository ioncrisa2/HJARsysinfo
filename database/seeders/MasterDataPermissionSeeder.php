<?php

namespace Database\Seeders;

use Illuminate\Support\Collection;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class MasterDataPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $legacyPermission = Permission::query()
            ->where('name', 'manage_master_data')
            ->where('guard_name', 'web')
            ->first();

        $granularPermissions = collect([
            'view_master_data',
            'create_master_data',
            'update_master_data',
            'update_master_data_status',
            'delete_master_data',
            'delete_any_master_data',
            'reorder_master_data',
            'view_geo_data',
            'create_geo_data',
            'update_geo_data',
            'delete_geo_data',
        ])->map(fn (string $permission): Permission => Permission::findOrCreate($permission, 'web'));

        $role = Role::query()
            ->whereRaw('LOWER(name) = ?', ['surveyor'])
            ->where('guard_name', 'web')
            ->first();

        if ($role) {
            $role->givePermissionTo($granularPermissions->all());
        }

        if ($legacyPermission) {
            $this->migrateLegacyAssignments($legacyPermission, $granularPermissions);
            $legacyPermission->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function migrateLegacyAssignments(Permission $legacyPermission, Collection $granularPermissions): void
    {
        $legacyPermission->roles()->each(
            fn ($role) => $role->givePermissionTo($granularPermissions->all())
        );

        $legacyPermission->users()->each(
            fn ($user) => $user->givePermissionTo($granularPermissions->all())
        );
    }
}
