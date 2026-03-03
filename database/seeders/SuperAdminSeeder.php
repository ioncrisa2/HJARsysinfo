<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdminRoleName = config('filament-shield.super_admin.name', 'super_admin');
        $role = Role::firstOrCreate([
            'name' => $superAdminRoleName,
            'guard_name' => 'web',
        ]);

        // Buat user admin
        // firstOrCreate akan mencari user dengan email ini, jika tidak ada, baru dibuat
        $admin = User::firstOrCreate(
            ['email' => 'admin@kjpp-hjar.co.id'], // <-- GANTI EMAIL
            [
                'name' => 'ADMIN',
                'password' => Hash::make('password'), // <-- GANTI PASSWORD
                'deactivated_at' => null
            ]
        );

        $admin->assignRole($role);
    }
}
