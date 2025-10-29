<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
class ShieldSuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1) Bersihkan cache permission
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 2) Nama role super admin mengikuti config Filament Shield
        $superAdminRoleName = config('filament-shield.super_admin.name', 'super_admin');
        $guard = config('filament.auth.guard', 'web'); // default 'web'

        // 3) Pastikan role super admin ada
        /** @var \Spatie\Permission\Models\Role $role */
        $role = Role::query()->firstOrCreate(
            ['name' => $superAdminRoleName, 'guard_name' => $guard],
            []
        );

        // 4) Ambil semua permissions yang ada (hasil shield:generate)
        $allPermissions = Permission::query()
            ->where('guard_name', $guard)
            ->pluck('name')
            ->all();

        // 5) Kaitkan semua permission ke role super admin
        $role->syncPermissions($allPermissions);

        // 6) Ambil admin email dari ENV (atau fallback)
        $adminEmail = env('SUPER_ADMIN_EMAIL', 'admin@example.com');

        // 7) Pastikan user ada; kalau belum, buat user default
        /** @var \App\Models\User $user */
        $user = User::query()->firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'Super Admin',
                'password' => Hash::make(env('SUPER_ADMIN_PASSWORD', 'password')),
            ]
        );

        // 8) Assign role super admin ke user
        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }
    }
}
