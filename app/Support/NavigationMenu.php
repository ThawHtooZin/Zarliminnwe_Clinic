<?php

namespace App\Support;

use App\Domain\Administration\Services\PermissionResolver;
use App\Models\User;

class NavigationMenu
{
    /**
     * @return array<int, array{key: string, label: string, items: array<int, array<string, mixed>>}>
     */
    public static function groupsFor(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        $permissionResolver = app(PermissionResolver::class);

        return collect(config('navigation.groups', []))
            ->map(function (array $group, string $key): array {
                return [
                    'key' => $key,
                    'label' => $group['label'],
                    'items' => $group['items'] ?? [],
                ];
            })
            ->map(function (array $group) use ($user, $permissionResolver): array {
                $group['items'] = collect($group['items'])
                    ->filter(fn (array $item): bool => $permissionResolver->canAccessScreen($user, $item['screen']))
                    ->values()
                    ->all();

                return $group;
            })
            ->filter(fn (array $group): bool => count($group['items']) > 0)
            ->values()
            ->all();
    }
}
