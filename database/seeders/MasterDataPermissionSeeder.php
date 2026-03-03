<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MasterDataPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Buat permission jika belum ada
        $permission = Permission::findOrCreate('manage_master_data', 'web');

        // Beri ke role surveyor jika ada
        $role = Role::query()
            ->whereRaw('LOWER(name) = ?', ['surveyor'])
            ->where('guard_name', 'web')
            ->first();

        if ($role) {
            $role->givePermissionTo($permission);
        }
    }
}
