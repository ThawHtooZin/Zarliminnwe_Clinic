<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Optional local/demo data. Not run by migrate --seed.
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
