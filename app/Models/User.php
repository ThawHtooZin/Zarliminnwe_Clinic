<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role_id', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_PHARMACIST = 'pharmacist';

    public const ROLE_STOCK_MANAGER = 'stock_manager';

    public const ROLE_CASHIER = 'cashier';

    public function assignedRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function getRoleAttribute(): ?string
    {
        return $this->assignedRole?->slug;
    }

    public function fill(array $attributes)
    {
        if (isset($attributes['role']) && ! isset($attributes['role_id'])) {
            $roleId = Role::query()->where('slug', $attributes['role'])->value('id');

            unset($attributes['role']);

            if ($roleId !== null) {
                $attributes['role_id'] = $roleId;
            }
        }

        return parent::fill($attributes);
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (array_key_exists('role', $user->getAttributes())) {
                $slug = $user->getAttributes()['role'];
                unset($user->attributes['role']);

                if ($user->role_id === null) {
                    $user->role_id = Role::query()->where('slug', $slug)->value('id');
                }
            }
        });
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
}
