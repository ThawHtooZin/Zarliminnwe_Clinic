<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Optional local/demo data. Not run by migrate --seed.
 *
 * Requires at least roles/permissions/users OR one real user in the DB
 * (StockLedgerSeeder uses an admin/active user for created_by).
 *
 * Usage: php artisan db:seed --class=DevelopmentDataSeeder
 */
class DevelopmentDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
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
