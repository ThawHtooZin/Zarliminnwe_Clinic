<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'email' => 'admin@zarliminnew.test',
                'name' => 'Clinic Admin',
                'role' => Role::SLUG_ADMIN,
            ],
            [
                'email' => 'cashier@zarliminnew.test',
                'name' => 'Clinic Cashier',
                'role' => Role::SLUG_CASHIER,
            ],
            [
                'email' => 'pharmacist@zarliminnew.test',
                'name' => 'Clinic Pharmacist',
                'role' => Role::SLUG_PHARMACIST,
            ],
            [
                'email' => 'stock_manager@zarliminnew.test',
                'name' => 'Clinic Stock Manager',
                'role' => Role::SLUG_STOCK_MANAGER,
            ],
        ];

        foreach ($users as $userData) {
            $roleId = Role::query()->where('slug', $userData['role'])->value('id');

            User::updateOrCreate([
                'email' => $userData['email'],
            ], [
                'name' => $userData['name'],
                'password' => Hash::make('password'),
                'role_id' => $roleId,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }
    }
}
