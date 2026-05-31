<?php

namespace Tests\Feature;

use App\Domain\Units\Services\UnitRelationshipService;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\PurchaseReceipt;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Product $product;

    private ProductUnit $box;

    private ProductUnit $strip;

    private ProductUnit $pill;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $category = ProductCategory::create(['name' => 'Medicines', 'is_active' => true]);
        $this->product = Product::create([
            'product_category_id' => $category->id,
            'name' => 'Paracetamol',
            'sku' => 'PARA',
            'track_batch' => true,
            'track_expiry' => true,
            'is_active' => true,
        ]);
        $this->box = ProductUnit::create([
            'product_id' => $this->product->id,
            'name' => 'Box',
            'abbreviation' => 'box',
            'level' => 1,
            'is_purchase_unit' => true,
            'is_sale_unit' => true,
            'sale_price' => 100,
        ]);
        $this->strip = ProductUnit::create([
            'product_id' => $this->product->id,
            'parent_product_unit_id' => $this->box->id,
            'name' => 'Strip',
            'abbreviation' => 'strip',
            'level' => 2,
            'conversion_factor' => 10,
            'is_purchase_unit' => true,
            'is_sale_unit' => true,
            'sale_price' => 12,
        ]);
        $this->pill = ProductUnit::create([
            'product_id' => $this->product->id,
            'parent_product_unit_id' => $this->strip->id,
            'name' => 'Pill',
            'abbreviation' => 'pill',
            'level' => 3,
            'conversion_factor' => 10,
            'is_purchase_unit' => false,
            'is_sale_unit' => true,
            'sale_price' => 2,
        ]);
    }

    public function test_unit_relationship_service_converts_and_formats_stock(): void
    {
        $service = app(UnitRelationshipService::class);

        $this->assertSame('20', $service->convert($this->box, $this->strip, 2));
        $this->assertSame('0.05', $service->convert($this->pill, $this->box, 5));

        $balance = StockBalance::create([
            'product_id' => $this->product->id,
            'product_unit_id' => $this->box->id,
            'quantity' => 1,
        ])->load('productUnit');

        $this->assertSame('100 pill', $service->formatStock($this->product, collect([$balance]), $this->pill));
    }

    public function test_admin_can_post_opening_stock(): void
    {
        $this->actingAs($this->admin)
            ->post(route('opening-stock.store'), [
                'product_id' => $this->product->id,
                'product_unit_id' => $this->box->id,
                'quantity' => 5,
                'batch_number' => 'B-001',
                'expires_at' => now()->addYear()->toDateString(),
                'reason' => 'Initial stock',
            ])
            ->assertRedirect(route('stock.index'));

        $this->assertDatabaseHas('stock_ledgers', [
            'product_id' => $this->product->id,
            'product_unit_id' => $this->box->id,
            'type' => StockLedger::TYPE_OPENING_STOCK,
            'direction' => StockLedger::DIRECTION_IN,
        ]);

        $this->assertDatabaseHas('stock_balances', [
            'product_id' => $this->product->id,
            'product_unit_id' => $this->box->id,
            'quantity' => 5,
        ]);
    }

    public function test_purchase_receipt_posting_updates_ledger_and_balance(): void
    {
        $supplier = Supplier::create(['name' => 'Supplier', 'is_active' => true]);

        $this->actingAs($this->admin)
            ->post(route('purchase-receipts.store'), [
                'supplier_id' => $supplier->id,
                'receipt_number' => 'PR-001',
                'received_at' => now()->toDateString(),
                'lines' => [
                    [
                        'product_id' => $this->product->id,
                        'product_unit_id' => $this->strip->id,
                        'quantity' => 12,
                        'unit_cost' => 1000,
                        'batch_number' => 'B-002',
                        'expires_at' => now()->addYear()->toDateString(),
                    ],
                ],
            ])
            ->assertRedirect();

        $receipt = PurchaseReceipt::where('receipt_number', 'PR-001')->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('purchase-receipts.post', $receipt))
            ->assertRedirect(route('purchase-receipts.show', $receipt));

        $this->assertSame(PurchaseReceipt::STATUS_POSTED, $receipt->fresh()->status);
        $this->assertDatabaseHas('stock_balances', [
            'product_id' => $this->product->id,
            'product_unit_id' => $this->strip->id,
            'quantity' => 12,
        ]);
    }

    public function test_cashier_cannot_access_phase_one_stock_posting(): void
    {
        $cashier = User::factory()->create(['role' => User::ROLE_CASHIER]);

        $this->actingAs($cashier)
            ->get(route('opening-stock.create'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'You do not have access to this page.');
    }
}
