<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Seed the default roles and their permissions.
     */
    public function run(): void
    {
        Role::query()->updateOrCreate(
            ['name' => Role::ADMIN],
            [
                'label' => 'Admin',
                'permissions' => Role::defaultAdminPermissions(),
            ],
        );

        Role::query()->updateOrCreate(
            ['name' => Role::GATE],
            [
                'label' => 'User',
                'permissions' => Role::defaultUserPermissions(),
            ],
        );
    }
}
