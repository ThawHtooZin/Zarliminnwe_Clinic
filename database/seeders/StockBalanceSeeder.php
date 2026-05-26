<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\StockBalance;
use App\Models\StockBatch;
use Illuminate\Database\Seeder;

class StockBalanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $balances = [
            ['PARA-500', 'PARA-B001', 'box', 8],
            ['PARA-500', 'PARA-B001', 'strip', 24],
            ['AMOX-500', 'AMOX-B001', 'box', 5],
            ['AMOX-500', 'AMOX-B001', 'strip', 10],
            ['CET-10', 'CET-B001', 'strip', 30],
            ['OME-20', 'OME-B001', 'box', 4],
            ['ORS-SACHET', 'ORS-B001', 'box', 12],
            ['ORS-SACHET', 'ORS-B001', 'sachet', 100],
            ['COUGH-100', 'COUGH-B001', 'btl', 20],
            ['NS-500', 'NS-B001', 'btl', 15],
            ['SYR-5ML', null, 'box', 10],
            ['SYR-5ML', null, 'pc', 120],
            ['GLOVE-MED', null, 'box', 8],
            ['GLOVE-MED', null, 'pair', 200],
            ['BETA-100', 'BETA-B001', 'btl', 18],
        ];

        foreach ($balances as [$sku, $batchNumber, $unitAbbreviation, $quantity]) {
            $product = Product::where('sku', $sku)->firstOrFail();
            $unit = ProductUnit::where('product_id', $product->id)
                ->where('abbreviation', $unitAbbreviation)
                ->firstOrFail();
            $batch = $batchNumber
                ? StockBatch::where('product_id', $product->id)->where('batch_number', $batchNumber)->firstOrFail()
                : null;

            StockBalance::updateOrCreate([
                'product_id' => $product->id,
                'stock_batch_id' => $batch?->id,
                'product_unit_id' => $unit->id,
            ], [
                'quantity' => $quantity,
            ]);
        }
    }
}
