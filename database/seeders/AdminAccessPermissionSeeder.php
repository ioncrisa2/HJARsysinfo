<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminAccessPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::findOrCreate('can_access_admin', 'web');

        $superAdmin = Role::whereRaw('LOWER(name) = ?', ['super_admin'])
            ->where('guard_name', 'web')
            ->first();

        if ($superAdmin) {
            $superAdmin->givePermissionTo($permission);
        }
    }
}
