<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class NonPropertyPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'view_any_data::non_property_comparable',
            'create_data::non_property_comparable',
            'update_data::non_property_comparable',
            'delete_data::non_property_comparable',
        ];

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $surveyorRole = Role::query()
            ->whereRaw('LOWER(name) = ?', ['surveyor'])
            ->first();

        if ($surveyorRole) {
            $surveyorRole->givePermissionTo($permissions);
        }
    }
}
