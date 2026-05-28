<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'slug' => Role::SLUG_ADMIN,
                'name' => 'Admin',
                'description' => 'Full system access.',
                'is_system' => true,
            ],
            [
                'slug' => Role::SLUG_PHARMACIST,
                'name' => 'Pharmacist',
                'description' => 'Pharmacy, finance, and stock operations.',
                'is_system' => true,
            ],
            [
                'slug' => Role::SLUG_CASHIER,
                'name' => 'Cashier',
                'description' => 'POS, patient visits, and finance entry.',
                'is_system' => true,
            ],
            [
                'slug' => Role::SLUG_STOCK_MANAGER,
                'name' => 'Stock Manager',
                'description' => 'Inventory and stock control.',
                'is_system' => true,
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['slug' => $role['slug']],
                $role,
            );
        }
    }
}
