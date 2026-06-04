<?php

namespace App\Domain\Administration\Services;

use App\Domain\Shared\Exceptions\DeletionBlockException;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserDeletionService
{
    /**
     * @throws DeletionBlockException
     */
    public function delete(User $user): void
    {
        if (Auth::id() === $user->id) {
            throw new DeletionBlockException([
                'your own account' => 1,
            ]);
        }

        $user->loadMissing('assignedRole');

        if ($user->assignedRole?->slug === Role::SLUG_ADMIN && $this->activeAdminCount() <= 1) {
            throw new DeletionBlockException([
                'last admin account' => 1,
            ]);
        }

        $user->delete();
    }

    private function activeAdminCount(): int
    {
        $adminRoleId = Role::query()->where('slug', Role::SLUG_ADMIN)->value('id');

        if ($adminRoleId === null) {
            return 0;
        }

        return User::query()
            ->where('role_id', $adminRoleId)
            ->where('is_active', true)
            ->count();
    }
}
