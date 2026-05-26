<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
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
