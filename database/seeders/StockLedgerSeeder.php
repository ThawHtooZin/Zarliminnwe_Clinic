<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\StockBatch;
use App\Models\StockLedger;
use App\Models\User;
use Illuminate\Database\Seeder;

class StockLedgerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@zarliminnew.test')->firstOrFail();
        $ledgers = [
            ['PARA-500', 'PARA-B001', 'box', 8, 18000],
            ['PARA-500', 'PARA-B001', 'strip', 24, 1800],
            ['AMOX-500', 'AMOX-B001', 'box', 5, 32000],
            ['AMOX-500', 'AMOX-B001', 'strip', 10, 3200],
            ['CET-10', 'CET-B001', 'strip', 30, 1500],
            ['OME-20', 'OME-B001', 'box', 4, 27000],
            ['ORS-SACHET', 'ORS-B001', 'box', 12, 10000],
            ['ORS-SACHET', 'ORS-B001', 'sachet', 100, 250],
            ['COUGH-100', 'COUGH-B001', 'btl', 20, 3000],
            ['NS-500', 'NS-B001', 'btl', 15, 1700],
            ['SYR-5ML', null, 'box', 10, 22000],
            ['SYR-5ML', null, 'pc', 120, 250],
            ['GLOVE-MED', null, 'box', 8, 12000],
            ['GLOVE-MED', null, 'pair', 200, 150],
            ['BETA-100', 'BETA-B001', 'btl', 18, 3800],
        ];

        foreach ($ledgers as [$sku, $batchNumber, $unitAbbreviation, $quantity, $unitCost]) {
            $product = Product::where('sku', $sku)->firstOrFail();
            $unit = ProductUnit::where('product_id', $product->id)
                ->where('abbreviation', $unitAbbreviation)
                ->firstOrFail();
            $batch = $batchNumber
                ? StockBatch::where('product_id', $product->id)->where('batch_number', $batchNumber)->firstOrFail()
                : null;

            StockLedger::updateOrCreate([
                'product_id' => $product->id,
                'stock_batch_id' => $batch?->id,
                'product_unit_id' => $unit->id,
                'type' => StockLedger::TYPE_OPENING_STOCK,
                'direction' => StockLedger::DIRECTION_IN,
                'reason' => 'Seeded opening stock',
            ], [
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'created_by' => $admin->id,
            ]);
        }
    }
}
