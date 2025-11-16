<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat atau cari role "super admin"
        $role = Role::firstOrCreate(['name' => 'super admin', 'guard_name' => 'web']);

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

        // Berikan role ke user
        $admin->assignRole($role);
    }
}
