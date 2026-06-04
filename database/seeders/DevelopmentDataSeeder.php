<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Initial server setup: roles, permissions, and users only.
 * Usage: php artisan db:seed --class=DevelopmentDataSeeder
 */
class DevelopmentDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
        ]);
    }
}
