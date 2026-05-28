<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= 'password',
            'role_id' => fn (): ?int => Role::query()->where('slug', User::ROLE_CASHIER)->value('id'),
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  \Illuminate\Database\Eloquent\Model|null  $parent
     */
    public function create($attributes = [], $parent = null)
    {
        if (isset($attributes['role'])) {
            $attributes['role_id'] = Role::query()
                ->where('slug', $attributes['role'])
                ->value('id');
            unset($attributes['role']);
        }

        return parent::create($attributes, $parent);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
