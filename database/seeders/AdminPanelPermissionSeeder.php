<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AdminPanelPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $canonical = Permission::findOrCreate('can_access_admin', 'web');
        $legacy = Permission::query()
            ->where('name', 'can_access_admin_panel')
            ->where('guard_name', 'web')
            ->first();

        if (! $legacy) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return;
        }

        $legacy->roles()->each(fn ($role) => $role->givePermissionTo($canonical));
        $legacy->users()->each(fn ($user) => $user->givePermissionTo($canonical));

        $legacy->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
