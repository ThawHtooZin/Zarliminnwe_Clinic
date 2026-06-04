<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Full dataset for local/dev. For production auth-only setup use DevelopmentDataSeeder.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
            IncomeCategorySeeder::class,
            ExpenseCategorySeeder::class,
            ProductCategorySeeder::class,
            ProductSeeder::class,
            ProductUnitSeeder::class,
            SupplierSeeder::class,
            StockBatchSeeder::class,
            StockBalanceSeeder::class,
            StockLedgerSeeder::class,
        ]);
    }
}
