<?php

namespace App\Domain\Administration\Services;

use App\Domain\Audit\Services\AuditLogger;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class AdminUserService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array{name: string, email: string, role_id: int, is_active?: bool, password?: string|null}  $data
     */
    public function createUser(array $data): User
    {
        if (blank($data['password'] ?? null)) {
            throw new InvalidArgumentException('Password is required when creating a user.');
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => $data['role_id'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->auditLogger->log('user.created', $user, null, $this->auditPayload($user));

        return $user;
    }

    /**
     * @param  array{name: string, email: string, role_id: int, is_active?: bool, password?: string|null}  $data
     */
    public function updateUser(User $user, array $data): User
    {
        $oldValues = $this->auditPayload($user);

        $attributes = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role_id' => $data['role_id'],
            'is_active' => $data['is_active'] ?? true,
        ];

        if (! blank($data['password'] ?? null)) {
            $attributes['password'] = Hash::make($data['password']);
        }

        $user->update($attributes);

        $this->auditLogger->log('user.updated', $user->fresh(), $oldValues, $this->auditPayload($user->fresh()));

        return $user->fresh();
    }

    public function resetPassword(User $user, string $password): User
    {
        $user->update([
            'password' => Hash::make($password),
        ]);

        $this->auditLogger->log('user.password_reset', $user->fresh(), null, [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return $user->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function auditPayload(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'role' => $user->role,
            'is_active' => $user->is_active,
        ];
    }
}
