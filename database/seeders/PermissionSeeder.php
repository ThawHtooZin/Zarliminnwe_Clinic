<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (config('permissions.routes', []) as $routeName => $name) {
            Permission::updateOrCreate(
                ['slug' => 'route.'.$routeName],
                [
                    'name' => $name,
                    'type' => Permission::TYPE_ROUTE,
                    'route_name' => $routeName,
                    'screen_key' => null,
                ],
            );
        }

        foreach (config('permissions.screens', []) as $screenKey => $name) {
            Permission::updateOrCreate(
                ['slug' => 'screen.'.$screenKey],
                [
                    'name' => $name,
                    'type' => Permission::TYPE_SCREEN,
                    'route_name' => null,
                    'screen_key' => $screenKey,
                ],
            );
        }
    }
}
