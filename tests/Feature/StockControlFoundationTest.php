<?php

namespace Tests\Feature;

use App\Domain\Inventory\Services\StockCountNumberGenerator;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\StockBatch;
use App\Models\StockCount;
use App\Models\StockCountLine;
use App\Models\StockLedger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StockControlFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_stock_count_tables_have_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('stock_counts', [
            'id',
            'count_number',
            'status',
            'counted_by',
            'reviewed_by',
            'started_at',
            'submitted_at',
            'posted_at',
            'notes',
        ]));

        $this->assertTrue(Schema::hasColumns('stock_count_lines', [
            'id',
            'stock_count_id',
            'product_id',
            'stock_batch_id',
            'product_unit_id',
            'expected_quantity',
            'counted_quantity',
            'variance_quantity',
            'adjustment_ledger_id',
            'notes',
        ]));
    }

    public function test_stock_count_model_relationships_and_status_helpers_work(): void
    {
        $countedBy = User::factory()->create(['role' => User::ROLE_STOCK_MANAGER]);
        $reviewedBy = User::factory()->create(['role' => User::ROLE_ADMIN]);
        [$product, $unit, $batch] = $this->createProductFixture();

        $stockCount = StockCount::create([
            'count_number' => 'SC-20260526-0001',
            'status' => StockCount::STATUS_DRAFT,
            'counted_by' => $countedBy->id,
            'reviewed_by' => $reviewedBy->id,
            'started_at' => now(),
        ]);

        $ledger = StockLedger::create([
            'product_id' => $product->id,
            'stock_batch_id' => $batch->id,
            'product_unit_id' => $unit->id,
            'type' => StockLedger::TYPE_ADJUSTMENT,
            'direction' => StockLedger::DIRECTION_IN,
            'quantity' => 1,
            'reason' => 'Stock count variance',
            'created_by' => $reviewedBy->id,
        ]);

        $line = StockCountLine::create([
            'stock_count_id' => $stockCount->id,
            'product_id' => $product->id,
            'stock_batch_id' => $batch->id,
            'product_unit_id' => $unit->id,
            'expected_quantity' => 10.5,
            'counted_quantity' => 11.5,
            'variance_quantity' => 1,
            'adjustment_ledger_id' => $ledger->id,
            'notes' => 'Found one extra strip',
        ]);

        $this->assertTrue($stockCount->isDraft());
        $this->assertFalse($stockCount->isSubmitted());
        $this->assertFalse($stockCount->isPosted());
        $this->assertFalse($stockCount->isCancelled());
        $this->assertTrue($stockCount->countedBy->is($countedBy));
        $this->assertTrue($stockCount->reviewedBy->is($reviewedBy));
        $this->assertTrue($stockCount->lines->first()->is($line));
        $this->assertTrue($line->stockCount->is($stockCount));
        $this->assertTrue($line->product->is($product));
        $this->assertTrue($line->productUnit->is($unit));
        $this->assertTrue($line->stockBatch->is($batch));
        $this->assertTrue($line->adjustmentLedger->is($ledger));
        $this->assertSame(10.5, (float) $line->expected_quantity);
        $this->assertSame(11.5, (float) $line->counted_quantity);
        $this->assertSame(1.0, (float) $line->variance_quantity);
    }

    public function test_stock_count_number_generator_creates_daily_unique_numbers(): void
    {
        Carbon::setTestNow('2026-05-26 09:00:00');

        $generator = app(StockCountNumberGenerator::class);

        $this->assertSame('SC-20260526-0001', $generator->generate());

        StockCount::create([
            'count_number' => 'SC-20260526-0001',
            'status' => StockCount::STATUS_DRAFT,
        ]);

        $this->assertSame('SC-20260526-0002', $generator->generate());
    }

    /**
     * @return array{Product, ProductUnit, StockBatch}
     */
    private function createProductFixture(): array
    {
        $category = ProductCategory::create([
            'name' => 'Medicines',
            'is_active' => true,
        ]);

        $product = Product::create([
            'product_category_id' => $category->id,
            'name' => 'Paracetamol 500mg',
            'sku' => 'PARA-500',
            'track_batch' => true,
            'track_expiry' => true,
            'is_active' => true,
        ]);

        $unit = ProductUnit::create([
            'product_id' => $product->id,
            'name' => 'Strip',
            'abbreviation' => 'strip',
            'level' => 1,
            'is_purchase_unit' => true,
            'is_sale_unit' => true,
            'sale_price' => 2800,
        ]);

        $batch = StockBatch::create([
            'product_id' => $product->id,
            'batch_number' => 'PARA-B001',
            'expires_at' => now()->addYear()->toDateString(),
            'received_at' => now()->toDateString(),
        ]);

        return [$product, $unit, $batch];
    }
}
