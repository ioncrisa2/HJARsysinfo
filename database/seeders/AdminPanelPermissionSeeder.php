<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminPanelPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::findOrCreate('can_access_admin_panel', 'web');

        // Opsional: pastikan super_admin memiliki permission ini (jika role ada)
        $superAdmin = Role::whereRaw('LOWER(name) = ?', ['super_admin'])
            ->where('guard_name', 'web')
            ->first();

        if ($superAdmin) {
            $superAdmin->givePermissionTo($permission);
        }
    }
}
