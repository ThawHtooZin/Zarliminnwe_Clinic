<?php

namespace Tests\Feature;

use App\Domain\Inventory\Services\StockCountService;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\StockBalance;
use App\Models\StockCount;
use App\Models\StockCountLine;
use App\Models\StockLedger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockCountWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $stockManager;

    private Product $product;

    private ProductUnit $strip;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->stockManager = User::factory()->create(['role' => User::ROLE_STOCK_MANAGER]);
        [$this->product, $this->strip] = $this->createProductFixture();
    }

    public function test_authorized_user_can_create_draft_stock_count_from_balances(): void
    {
        $balance = $this->createBalance(10);

        $this->actingAs($this->stockManager)
            ->post(route('stock-counts.store'), [
                'notes' => 'Monthly shelf count',
                'stock_balance_ids' => [$balance->id],
            ])
            ->assertRedirect();

        $stockCount = StockCount::with('lines')->firstOrFail();

        $this->assertSame(StockCount::STATUS_DRAFT, $stockCount->status);
        $this->assertSame($this->stockManager->id, $stockCount->counted_by);
        $this->assertNotNull($stockCount->started_at);
        $this->assertStringStartsWith('SC-', $stockCount->count_number);
        $this->assertCount(1, $stockCount->lines);
        $this->assertSame(10.0, (float) $stockCount->lines->first()->expected_quantity);
        $this->assertSame(10.0, (float) $stockCount->lines->first()->counted_quantity);
        $this->assertSame(0.0, (float) $stockCount->lines->first()->variance_quantity);
        $this->assertDatabaseHas('audit_logs', ['action' => 'stock_count.created']);
    }

    public function test_draft_stock_count_can_be_saved_multiple_times_and_variance_is_calculated(): void
    {
        $stockCount = $this->createDraftCountWithBalance(10);
        $line = $stockCount->lines()->firstOrFail();

        $this->actingAs($this->stockManager)
            ->put(route('stock-counts.update', $stockCount), [
                'lines' => [
                    ['id' => $line->id, 'counted_quantity' => 8, 'notes' => 'Two missing'],
                ],
            ])
            ->assertRedirect(route('stock-counts.show', $stockCount));

        $line = StockCountLine::findOrFail($line->id);
        $this->assertSame(8.0, (float) $line->counted_quantity);
        $this->assertSame(-2.0, (float) $line->variance_quantity);

        $this->actingAs($this->stockManager)
            ->put(route('stock-counts.update', $stockCount), [
                'lines' => [
                    ['id' => $line->id, 'counted_quantity' => 9, 'notes' => 'One missing'],
                ],
            ])
            ->assertRedirect(route('stock-counts.show', $stockCount));

        $line = StockCountLine::findOrFail($line->id);
        $this->assertSame(9.0, (float) $line->counted_quantity);
        $this->assertSame(-1.0, (float) $line->variance_quantity);
        $this->assertDatabaseHas('audit_logs', ['action' => 'stock_count.updated']);
    }

    public function test_negative_counted_quantity_is_rejected(): void
    {
        $stockCount = $this->createDraftCountWithBalance(10);
        $line = $stockCount->lines()->firstOrFail();

        $this->actingAs($this->stockManager)
            ->from(route('stock-counts.show', $stockCount))
            ->put(route('stock-counts.update', $stockCount), [
                'lines' => [
                    ['id' => $line->id, 'counted_quantity' => -1],
                ],
            ])
            ->assertRedirect(route('stock-counts.show', $stockCount))
            ->assertSessionHasErrors('lines.0.counted_quantity');
    }

    public function test_draft_stock_count_can_be_submitted(): void
    {
        $stockCount = $this->createDraftCountWithBalance(10);

        $this->actingAs($this->stockManager)
            ->post(route('stock-counts.submit', $stockCount))
            ->assertRedirect(route('stock-counts.show', $stockCount));

        $stockCount->refresh();

        $this->assertSame(StockCount::STATUS_SUBMITTED, $stockCount->status);
        $this->assertNotNull($stockCount->submitted_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'stock_count.submitted']);
    }

    public function test_positive_variance_posts_adjustment_and_increases_stock_balance(): void
    {
        $balance = $this->createBalance(10);
        $stockCount = $this->createDraftCountFromBalances([$balance->id]);
        $line = $stockCount->lines()->firstOrFail();

        $this->actingAs($this->stockManager)
            ->put(route('stock-counts.update', $stockCount), [
                'lines' => [
                    ['id' => $line->id, 'counted_quantity' => 12, 'notes' => 'Found extra stock'],
                ],
            ])
            ->assertRedirect(route('stock-counts.show', $stockCount));

        $this->actingAs($this->stockManager)
            ->post(route('stock-counts.submit', $stockCount))
            ->assertRedirect(route('stock-counts.show', $stockCount));

        $this->actingAs($this->stockManager)
            ->post(route('stock-counts.post', $stockCount))
            ->assertRedirect(route('stock-counts.show', $stockCount));

        $balance->refresh();
        $line = StockCountLine::findOrFail($line->id);
        $ledger = StockLedger::firstOrFail();

        $this->assertSame(12.0, (float) $balance->quantity);
        $this->assertSame(StockLedger::TYPE_ADJUSTMENT, $ledger->type);
        $this->assertSame(StockLedger::DIRECTION_IN, $ledger->direction);
        $this->assertSame(2.0, (float) $ledger->quantity);
        $this->assertSame(StockCount::class, $ledger->reference_type);
        $this->assertSame($stockCount->id, $ledger->reference_id);
        $this->assertSame($ledger->id, $line->adjustment_ledger_id);
        $this->assertSame(StockCount::STATUS_POSTED, $stockCount->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'stock_count.posted']);
    }

    public function test_negative_variance_posts_adjustment_and_decreases_stock_balance(): void
    {
        $balance = $this->createBalance(10);
        $stockCount = $this->createDraftCountFromBalances([$balance->id]);
        $line = $stockCount->lines()->firstOrFail();

        $this->actingAs($this->stockManager)
            ->put(route('stock-counts.update', $stockCount), [
                'lines' => [
                    ['id' => $line->id, 'counted_quantity' => 8, 'notes' => 'Shelf shortage'],
                ],
            ])
            ->assertRedirect(route('stock-counts.show', $stockCount));

        $this->actingAs($this->stockManager)
            ->post(route('stock-counts.submit', $stockCount))
            ->assertRedirect(route('stock-counts.show', $stockCount));

        $this->actingAs($this->stockManager)
            ->post(route('stock-counts.post', $stockCount))
            ->assertRedirect(route('stock-counts.show', $stockCount));

        $balance->refresh();
        $ledger = StockLedger::firstOrFail();

        $this->assertSame(8.0, (float) $balance->quantity);
        $this->assertSame(StockLedger::TYPE_ADJUSTMENT, $ledger->type);
        $this->assertSame(StockLedger::DIRECTION_OUT, $ledger->direction);
        $this->assertSame(2.0, (float) $ledger->quantity);
    }

    public function test_posted_stock_count_cannot_be_edited(): void
    {
        $balance = $this->createBalance(10);
        $stockCount = $this->createDraftCountFromBalances([$balance->id]);
        $line = $stockCount->lines()->firstOrFail();

        $this->actingAs($this->stockManager)
            ->post(route('stock-counts.submit', $stockCount))
            ->assertRedirect(route('stock-counts.show', $stockCount));

        $this->actingAs($this->stockManager)
            ->post(route('stock-counts.post', $stockCount))
            ->assertRedirect(route('stock-counts.show', $stockCount));

        $this->actingAs($this->stockManager)
            ->from(route('stock-counts.show', $stockCount))
            ->put(route('stock-counts.update', $stockCount), [
                'lines' => [
                    ['id' => $line->id, 'counted_quantity' => 99],
                ],
            ])
            ->assertRedirect(route('stock-counts.show', $stockCount))
            ->assertSessionHasErrors('lines');

        $this->assertSame(10.0, (float) StockCountLine::findOrFail($line->id)->counted_quantity);
    }

    public function test_stock_manager_can_cancel_draft_stock_count(): void
    {
        $stockCount = $this->createDraftCountWithBalance(10);

        $this->actingAs($this->stockManager)
            ->post(route('stock-counts.cancel', $stockCount))
            ->assertRedirect(route('stock-counts.show', $stockCount));

        $this->assertSame(StockCount::STATUS_CANCELLED, $stockCount->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'stock_count.cancelled']);
    }

    public function test_cashier_cannot_access_stock_counts(): void
    {
        $cashier = User::factory()->create(['role' => User::ROLE_CASHIER]);

        $this->actingAs($cashier)
            ->get(route('stock-counts.index'))
            ->assertForbidden();
    }

    public function test_pharmacist_can_submit_but_cannot_post_stock_count(): void
    {
        $pharmacist = User::factory()->create(['role' => User::ROLE_PHARMACIST]);
        $stockCount = $this->createDraftCountWithBalance(10);

        $this->actingAs($pharmacist)
            ->post(route('stock-counts.submit', $stockCount))
            ->assertRedirect(route('stock-counts.show', $stockCount));

        $this->actingAs($pharmacist)
            ->post(route('stock-counts.post', $stockCount))
            ->assertForbidden();
    }

    private function createDraftCountWithBalance(float $quantity): StockCount
    {
        $balance = $this->createBalance($quantity);

        return $this->createDraftCountFromBalances([$balance->id]);
    }

    /**
     * @param  array<int>  $balanceIds
     */
    private function createDraftCountFromBalances(array $balanceIds): StockCount
    {
        $this->actingAs($this->stockManager);

        return app(StockCountService::class)->createDraftFromBalances($balanceIds, $this->stockManager);
    }

    private function createBalance(float $quantity): StockBalance
    {
        return StockBalance::create([
            'product_id' => $this->product->id,
            'product_unit_id' => $this->strip->id,
            'quantity' => $quantity,
        ]);
    }

    /**
     * @return array{Product, ProductUnit}
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
}
