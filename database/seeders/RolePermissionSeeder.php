<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $grants = config('permissions.role_grants', []);
        $allPermissionIds = Permission::query()->pluck('id', 'slug');

        foreach ($grants as $roleSlug => $grant) {
            $role = Role::query()->where('slug', $roleSlug)->first();

            if ($role === null) {
                continue;
            }

            if ($roleSlug === Role::SLUG_ADMIN || in_array('*', $grant, true)) {
                $role->permissions()->sync($allPermissionIds->values()->all());

                continue;
            }

            $routeNames = $grant['routes'] ?? [];
            $screenKeys = $grant['screens'] ?? [];

            if (isset($grant['inherits'])) {
                $parentGrant = $grants[$grant['inherits']] ?? [];
                $routeNames = array_values(array_unique(array_merge($parentGrant['routes'] ?? [], $routeNames)));
                $screenKeys = array_values(array_unique(array_merge($parentGrant['screens'] ?? [], $screenKeys)));
            }

            $permissionIds = collect($routeNames)
                ->map(fn (string $routeName): ?int => $allPermissionIds->get('route.'.$routeName))
                ->merge(collect($screenKeys)->map(fn (string $screenKey): ?int => $allPermissionIds->get('screen.'.$screenKey)))
                ->filter()
                ->unique()
                ->values()
                ->all();

            $role->permissions()->sync($permissionIds);
        }
    }
}
