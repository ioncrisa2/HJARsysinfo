<?php

namespace Database\Seeders;

use App\Support\AppAccess;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AppAccessPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(AppAccess::permissions())
            ->map(fn (string $permission): Permission => Permission::findOrCreate($permission, 'web'));

        $this->migratePermission('view_admin_search', 'view_search');
        $this->removePermission('view_admin_dashboard');
        $this->removePermission('can_access_admin_panel');
        $this->removePermission('can_access_admin');

        $superAdmin = Role::whereRaw('LOWER(name) = ?', ['super_admin'])
            ->where('guard_name', 'web')
            ->first();

        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions->all());
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function migratePermission(string $legacyName, string $canonicalName): void
    {
        $legacy = Permission::query()
            ->where('name', $legacyName)
            ->where('guard_name', 'web')
            ->first();

        if (! $legacy) {
            return;
        }

        $canonical = Permission::findByName($canonicalName, 'web');
        $legacy->roles()->each(fn (Role $role) => $role->givePermissionTo($canonical));
        $legacy->users()->each(fn ($user) => $user->givePermissionTo($canonical));
        $legacy->delete();
    }

    private function removePermission(string $name): void
    {
        Permission::query()
            ->where('name', $name)
            ->where('guard_name', 'web')
            ->first()
            ?->delete();
    }
}
