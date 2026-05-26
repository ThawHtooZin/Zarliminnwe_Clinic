<?php

namespace Database\Seeders;

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
        User::updateOrCreate([
            'email' => 'admin@zarliminnew.test',
        ], [
            'name' => 'Clinic Admin',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        User::updateOrCreate([
            'email' => 'cashier@zarliminnew.test',
        ], [
            'name' => 'Clinic Cashier',
            'password' => Hash::make('password'),
            'role' => User::ROLE_CASHIER,
            'email_verified_at' => now(),
        ]);

        User::updateOrCreate([
            'email' => 'pharmacist@zarliminnew.test',
        ], [
            'name' => 'Clinic Pharmacist',
            'password' => Hash::make('password'),
            'role' => User::ROLE_PHARMACIST,
            'email_verified_at' => now(),
        ]);
    }
}
