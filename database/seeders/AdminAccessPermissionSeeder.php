<?php

namespace Database\Seeders;

use App\Support\AdminAccess;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminAccessPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(AdminAccess::permissions())
            ->map(fn (string $permission): Permission => Permission::findOrCreate($permission, 'web'));

        $superAdmin = Role::whereRaw('LOWER(name) = ?', ['super_admin'])
            ->where('guard_name', 'web')
            ->first();

        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions->all());
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
