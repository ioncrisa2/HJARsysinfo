<?php

namespace Database\Seeders;

use App\Support\AppAccess;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PembandingAccessRoleSeeder extends Seeder
{
    private const GUARD = 'web';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'view_map',
            'view_any_data::pembanding',
            'view_data::pembanding',
            'create_data::pembanding',
            'update_data::pembanding',
            'update_own_data::pembanding',
            'delete_data::pembanding',
            'delete_any_data::pembanding',
            'force_delete_data::pembanding',
            'force_delete_any_data::pembanding',
            'restore_data::pembanding',
            'restore_any_data::pembanding',
            'replicate_data::pembanding',
            'reorder_data::pembanding',
            'export_data::pembanding',
            'export_sensitive_data::pembanding',
            'bulk_import_data::pembanding',
            ...AppAccess::permissions(),
        ];

        $permissionModels = collect($permissions)
            ->mapWithKeys(fn (string $permission): array => [
                $permission => Permission::findOrCreate($permission, self::GUARD),
            ]);

        $pimpinan = Role::query()->firstOrCreate([
            'name' => 'pimpinan',
            'guard_name' => self::GUARD,
        ]);

        $pimpinan->syncPermissions($permissionModels->only([
            'view_map',
            'view_any_data::pembanding',
            'view_data::pembanding',
            'create_data::pembanding',
            'update_data::pembanding',
            'widget_Map',
            'widget_StatsOverview',
        ])->values()->all());

        $contributor = Role::query()->firstOrCreate([
            'name' => 'data_contributor',
            'guard_name' => self::GUARD,
        ]);

        $contributor->syncPermissions($permissionModels->only([
            'view_map',
            'view_any_data::pembanding',
            'view_data::pembanding',
            'create_data::pembanding',
            'update_own_data::pembanding',
            'widget_Map',
            'widget_StatsOverview',
        ])->values()->all());

        $surveyor = Role::query()->firstOrCreate([
            'name' => 'surveyor',
            'guard_name' => self::GUARD,
        ]);

        $surveyor->givePermissionTo($permissionModels->only([
            'view_map',
            'view_any_data::pembanding',
            'view_data::pembanding',
            'create_data::pembanding',
            'update_own_data::pembanding',
            'widget_Map',
            'widget_StatsOverview',
            'widget_TopContributorTable',
        ])->values()->all());

        $bulkImport = Role::query()->firstOrCreate([
            'name' => 'bulk_import',
            'guard_name' => self::GUARD,
        ]);

        $bulkImport->syncPermissions($permissionModels->only([
            'bulk_import_data::pembanding',
        ])->values()->all());

        $superAdmin = Role::query()->firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => self::GUARD,
        ]);

        $superAdmin->givePermissionTo($permissionModels->values()->all());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
