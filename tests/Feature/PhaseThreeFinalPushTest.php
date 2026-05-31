<?php

namespace Tests\Feature;

use App\Domain\Inventory\Services\ExpiryAlertService;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\StockBalance;
use App\Models\StockBatch;
use App\Models\StockCount;
use App\Models\StockLedger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseThreeFinalPushTest extends TestCase
{
    use RefreshDatabase;

    private User $stockManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->stockManager = User::factory()->create(['role' => User::ROLE_STOCK_MANAGER]);
    }

    public function test_expiry_service_returns_expired_and_near_expiry_batches_with_remaining_stock(): void
    {
        [$product, $unit] = $this->createTrackedProduct('PARA-500', 'Paracetamol 500mg');
        $expiredBatch = $this->createBatch($product, 'EXP-001', now()->subDay()->toDateString());
        $nearBatch = $this->createBatch($product, 'EXP-030', now()->addDays(30)->toDateString());
        $zeroBatch = $this->createBatch($product, 'EXP-ZERO', now()->addDays(20)->toDateString());

        $this->createBalance($product, $unit, 5, $expiredBatch);
        $this->createBalance($product, $unit, 3, $nearBatch);
        $this->createBalance($product, $unit, 0, $zeroBatch);

        $alerts = app(ExpiryAlertService::class)->getExpiringBatches(90);

        $this->assertCount(2, $alerts);
        $this->assertSame(['expired', 'within_30_days'], $alerts->pluck('severity')->all());
        $this->assertFalse($alerts->pluck('batch_number')->contains('EXP-ZERO'));
    }

    public function test_authorized_user_can_view_expiry_page_and_cashier_cannot(): void
    {
        [$product, $unit] = $this->createTrackedProduct('PARA-500', 'Paracetamol 500mg');
        $batch = $this->createBatch($product, 'EXP-001', now()->subDay()->toDateString());
        $this->createBalance($product, $unit, 5, $batch);

        $this->actingAs($this->stockManager)
            ->get(route('stock-control.expiry', ['expired_only' => 1]))
            ->assertOk()
            ->assertSee('Expiry Alerts')
            ->assertSee('EXP-001');

        $cashier = User::factory()->create(['role' => User::ROLE_CASHIER]);

        $this->actingAs($cashier)
            ->get(route('stock-control.expiry'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'You do not have access to this page.');
    }

    public function test_expired_stock_adjustment_posts_out_ledger_and_reduces_balance(): void
    {
        [$product, $unit] = $this->createTrackedProduct('PARA-500', 'Paracetamol 500mg');
        $batch = $this->createBatch($product, 'EXP-001', now()->subDay()->toDateString());
        $balance = $this->createBalance($product, $unit, 5, $batch);

        $this->actingAs($this->stockManager)
            ->post(route('stock-adjustments.store'), [
                'stock_balance_id' => $balance->id,
                'quantity' => 3,
                'reason' => 'Expired stock removal',
            ])
            ->assertRedirect(route('stock-control.expiry'));

        $balance->refresh();
        $ledger = StockLedger::firstOrFail();

        $this->assertSame(2.0, (float) $balance->quantity);
        $this->assertSame(StockLedger::TYPE_ADJUSTMENT, $ledger->type);
        $this->assertSame(StockLedger::DIRECTION_OUT, $ledger->direction);
        $this->assertSame($batch->id, $ledger->stock_batch_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'stock_adjustment.posted']);
    }

    public function test_stock_on_hand_report_filters_by_category(): void
    {
        [$medicineProduct, $medicineUnit] = $this->createTrackedProduct('PARA-500', 'Paracetamol 500mg', 'Medicines');
        [$supplyProduct, $supplyUnit] = $this->createTrackedProduct('BAND-001', 'Bandage', 'Supplies');
        $this->createBalance($medicineProduct, $medicineUnit, 5);
        $this->createBalance($supplyProduct, $supplyUnit, 7);

        $this->actingAs($this->stockManager)
            ->get(route('reports.stock-on-hand', ['category_id' => $medicineProduct->product_category_id]))
            ->assertOk()
            ->assertSee('Paracetamol 500mg')
            ->assertDontSee('Bandage');
    }

    public function test_stock_movement_report_filters_by_type_and_direction(): void
    {
        [$product, $unit] = $this->createTrackedProduct('PARA-500', 'Paracetamol 500mg');

        StockLedger::create([
            'product_id' => $product->id,
            'product_unit_id' => $unit->id,
            'type' => StockLedger::TYPE_ADJUSTMENT,
            'direction' => StockLedger::DIRECTION_IN,
            'quantity' => 2,
            'reason' => 'Correction',
            'created_by' => $this->stockManager->id,
        ]);
        StockLedger::create([
            'product_id' => $product->id,
            'product_unit_id' => $unit->id,
            'type' => StockLedger::TYPE_SALE,
            'direction' => StockLedger::DIRECTION_OUT,
            'quantity' => 1,
            'reason' => 'Sale',
            'created_by' => $this->stockManager->id,
        ]);

        $this->actingAs($this->stockManager)
            ->get(route('reports.stock-movements', [
                'type' => StockLedger::TYPE_ADJUSTMENT,
                'direction' => StockLedger::DIRECTION_IN,
            ]))
            ->assertOk()
            ->assertSee('Correction')
            ->assertDontSee('>Sale</td>', false);
    }

    public function test_low_stock_and_expiry_reports_render_expected_rows(): void
    {
        [$product, $unit] = $this->createTrackedProduct('PARA-500', 'Paracetamol 500mg');
        $product->update([
            'reorder_product_unit_id' => $unit->id,
            'reorder_quantity' => 10,
        ]);
        $batch = $this->createBatch($product, 'EXP-001', now()->addDays(20)->toDateString());
        $this->createBalance($product, $unit, 5, $batch);

        $this->actingAs($this->stockManager)
            ->get(route('reports.low-stock'))
            ->assertOk()
            ->assertSee('Paracetamol 500mg')
            ->assertSee('5 strip');

        $this->actingAs($this->stockManager)
            ->get(route('reports.expiry', ['days' => 30]))
            ->assertOk()
            ->assertSee('EXP-001')
            ->assertSee('within 30 days');
    }

    public function test_stock_adjustment_report_links_stock_count_source(): void
    {
        [$product, $unit] = $this->createTrackedProduct('PARA-500', 'Paracetamol 500mg');
        $stockCount = StockCount::create([
            'count_number' => 'SC-20260526-0001',
            'status' => StockCount::STATUS_POSTED,
        ]);

        StockLedger::create([
            'product_id' => $product->id,
            'product_unit_id' => $unit->id,
            'type' => StockLedger::TYPE_ADJUSTMENT,
            'direction' => StockLedger::DIRECTION_IN,
            'quantity' => 2,
            'reference_type' => StockCount::class,
            'reference_id' => $stockCount->id,
            'reason' => 'Stock count variance',
            'created_by' => $this->stockManager->id,
        ]);

        $this->actingAs($this->stockManager)
            ->get(route('reports.stock-adjustments'))
            ->assertOk()
            ->assertSee('Stock count variance')
            ->assertSee('#'.$stockCount->id);
    }

    public function test_guest_cannot_access_stock_control_routes(): void
    {
        [$product, $unit] = $this->createTrackedProduct('PARA-500', 'Paracetamol 500mg');
        $balance = $this->createBalance($product, $unit, 5);

        $this->get(route('stock-control.expiry'))->assertRedirect(route('login'));
        $this->get(route('reports.stock-on-hand'))->assertRedirect(route('login'));
        $this->get(route('stock-adjustments.create', ['stock_balance_id' => $balance->id]))->assertRedirect(route('login'));
    }

    /**
     * @return array{Product, ProductUnit}
     */
    private function createTrackedProduct(string $sku, string $name, string $categoryName = 'Medicines'): array
    {
        $category = ProductCategory::create([
            'name' => $categoryName,
            'is_active' => true,
        ]);

        $product = Product::create([
            'product_category_id' => $category->id,
            'name' => $name,
            'sku' => $sku,
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

        return [$product, $unit];
    }

    private function createBatch(Product $product, string $batchNumber, string $expiresAt): StockBatch
    {
        return StockBatch::create([
            'product_id' => $product->id,
            'batch_number' => $batchNumber,
            'expires_at' => $expiresAt,
            'received_at' => now()->toDateString(),
        ]);
    }

    private function createBalance(Product $product, ProductUnit $unit, float $quantity, ?StockBatch $batch = null): StockBalance
    {
        return StockBalance::create([
            'product_id' => $product->id,
            'stock_batch_id' => $batch?->id,
            'product_unit_id' => $unit->id,
            'quantity' => $quantity,
        ]);
    }
}
