<?php

namespace App\Domain\Administration\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

class PermissionResolver
{
    /**
     * @var array<int, Collection<int, string>>
     */
    private array $cache = [];

    public function canAccessRoute(User $user, ?string $routeName): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($routeName === null) {
            return true;
        }

        $permissionSlugs = $this->permissionSlugsFor($user);

        if (! $permissionSlugs->contains('route.'.$routeName)) {
            return false;
        }

        $requiredScreen = $this->relatedScreenForRoute($routeName);

        if ($requiredScreen === null) {
            return true;
        }

        return $permissionSlugs->contains('screen.'.$requiredScreen);
    }

    public function canAccessScreen(User $user, string $screenKey): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $this->permissionSlugsFor($user)->contains('screen.'.$screenKey);
    }

    /**
     * @return Collection<int, string>
     */
    public function permissionSlugsFor(User $user): Collection
    {
        if (isset($this->cache[$user->id])) {
            return $this->cache[$user->id];
        }

        $user->loadMissing('assignedRole.permissions');

        $role = $user->assignedRole;

        if ($role === null) {
            return $this->cache[$user->id] = collect();
        }

        return $this->cache[$user->id] = $role->permissions->pluck('slug');
    }

    /**
     * @return Collection<int, Permission>
     */
    public function permissionsGroupedForRole(Role $role): Collection
    {
        return $role->permissions()
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->groupBy('type');
    }

    private function relatedScreenForRoute(string $routeName): ?string
    {
        $screenKeys = array_keys(config('permissions.screens', []));

        usort($screenKeys, fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        foreach ($screenKeys as $screenKey) {
            if ($routeName === $screenKey || str_starts_with($routeName, $screenKey.'.') || str_starts_with($routeName, $screenKey.'-')) {
                return $screenKey;
            }
        }

        return null;
    }
}
