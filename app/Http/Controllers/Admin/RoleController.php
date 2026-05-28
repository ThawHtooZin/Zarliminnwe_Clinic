<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\Services\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(): View
    {
        $roles = Role::query()
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function edit(Role $role): View
    {
        $role->load('permissions');

        $permissions = Permission::query()
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->groupBy('type');

        $screenPermissions = $permissions->get(Permission::TYPE_SCREEN, collect());
        $routePermissions = $permissions->get(Permission::TYPE_ROUTE, collect());

        $routePermissionGroups = $routePermissions
            ->groupBy(function (Permission $permission): string {
                $routeName = $permission->route_name ?: $permission->name;

                return str($routeName)->before('.')->headline()->toString();
            })
            ->sortKeys();

        $routeToScreenKey = $routePermissions
            ->mapWithKeys(function (Permission $permission): array {
                $routeName = $permission->route_name ?: '';

                return [$permission->id => $this->relatedScreenKeyForRoute($routeName)];
            })
            ->all();

        return view('admin.roles.edit', compact('role', 'screenPermissions', 'routePermissionGroups', 'routeToScreenKey'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        if ($role->isSystemRole() && $role->slug === Role::SLUG_ADMIN) {
            return redirect()
                ->route('admin.roles.edit', $role)
                ->with('status', 'Admin role always has full access.');
        }

        $validated = $request->validate([
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        $oldPermissionIds = $role->permissions()->pluck('permissions.id')->all();
        $newPermissionIds = $this->filterPermissionIdsByScreenRules($validated['permission_ids'] ?? []);

        $role->permissions()->sync($newPermissionIds);

        $this->auditLogger->log('role.permissions_updated', $role, [
            'permission_ids' => $oldPermissionIds,
        ], [
            'permission_ids' => $newPermissionIds,
        ]);

        return redirect()
            ->route('admin.roles.edit', $role)
            ->with('status', 'Role permissions updated.');
    }

    /**
     * @param  array<int, int|string>  $permissionIds
     * @return array<int, int>
     */
    private function filterPermissionIdsByScreenRules(array $permissionIds): array
    {
        $selectedIds = array_values(array_unique(array_map('intval', $permissionIds)));

        if ($selectedIds === []) {
            return [];
        }

        $selectedPermissions = Permission::query()
            ->whereIn('id', $selectedIds)
            ->get(['id', 'type', 'route_name', 'screen_key']);

        $selectedScreenKeys = $selectedPermissions
            ->where('type', Permission::TYPE_SCREEN)
            ->pluck('screen_key')
            ->filter()
            ->all();

        $allowedRoutePermissionIds = $selectedPermissions
            ->where('type', Permission::TYPE_ROUTE)
            ->filter(function (Permission $permission) use ($selectedScreenKeys): bool {
                $requiredScreenKey = $this->relatedScreenKeyForRoute($permission->route_name ?? '');

                return $requiredScreenKey === null || in_array($requiredScreenKey, $selectedScreenKeys, true);
            })
            ->pluck('id')
            ->all();

        $allowedScreenPermissionIds = $selectedPermissions
            ->where('type', Permission::TYPE_SCREEN)
            ->pluck('id')
            ->all();

        return array_values(array_merge($allowedScreenPermissionIds, $allowedRoutePermissionIds));
    }

    private function relatedScreenKeyForRoute(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        $screenKeys = array_keys(config('permissions.screens', []));
        usort($screenKeys, fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        foreach ($screenKeys as $screenKey) {
            if (
                $routeName === $screenKey
                || str_starts_with($routeName, $screenKey.'.')
                || str_starts_with($routeName, $screenKey.'-')
            ) {
                return $screenKey;
            }
        }

        return null;
    }
}
