<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\StockBatch;
use Illuminate\Database\Seeder;

class StockBatchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $batches = [
            ['PARA-500', 'PARA-B001', '2027-12-31'],
            ['AMOX-500', 'AMOX-B001', '2027-10-31'],
            ['CET-10', 'CET-B001', '2028-01-31'],
            ['OME-20', 'OME-B001', '2027-11-30'],
            ['ORS-SACHET', 'ORS-B001', '2028-03-31'],
            ['COUGH-100', 'COUGH-B001', '2027-08-31'],
            ['NS-500', 'NS-B001', '2027-09-30'],
            ['BETA-100', 'BETA-B001', '2028-02-28'],
        ];

        foreach ($batches as [$sku, $batchNumber, $expiresAt]) {
            $product = Product::where('sku', $sku)->firstOrFail();

            StockBatch::updateOrCreate([
                'product_id' => $product->id,
                'batch_number' => $batchNumber,
                'expires_at' => $expiresAt,
            ], [
                'received_at' => '2026-05-01',
            ]);
        }
    }
}
