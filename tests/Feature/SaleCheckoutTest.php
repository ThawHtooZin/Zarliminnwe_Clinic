<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->cashier = User::factory()->create([
            'role' => User::ROLE_CASHIER,
        ]);
    }

    public function test_sales_store_completes_sale_without_patient(): void
    {
        [$product, $box, $strip] = $this->createProductWithUnits();

        StockBalance::create([
            'product_id' => $product->id,
            'product_unit_id' => $strip->id,
            'quantity' => 10,
        ]);

        $response = $this->actingAs($this->cashier)
            ->from(route('sales.pos'))
            ->post(route('sales.store'), $this->checkoutPayload([
                ['productId' => $product->id, 'unitId' => $strip->id, 'quantity' => 2, 'unitPrice' => 2800],
            ], 6000));

        $response
            ->assertRedirect(route('sales.pos'))
            ->assertSessionHas('status');

        $sale = Sale::with('lines')->firstOrFail();

        $this->assertSame(Sale::STATUS_COMPLETED, $sale->status);
        $this->assertNull($sale->patient_visit_record_id);
        $this->assertSame($this->cashier->id, $sale->sold_by);
        $this->assertSame($product->id, $sale->lines->first()->product_id);
        $this->assertSame($strip->id, $sale->lines->first()->product_unit_id);
        $this->assertSame(2.0, (float) $sale->lines->first()->quantity);
        $this->assertSame(5600.0, (float) $sale->grand_total);
        $this->assertSame(400.0, (float) $sale->change_amount);

        $this->assertStringNotContainsString('will be completed in Epic 3', session('status'));
        $this->assertNotSame($box->id, $sale->lines->first()->product_unit_id);
    }

    public function test_same_unit_checkout_posts_out_ledger_and_reduces_stock_balance(): void
    {
        [$product, , $strip] = $this->createProductWithUnits();

        $balance = StockBalance::create([
            'product_id' => $product->id,
            'product_unit_id' => $strip->id,
            'quantity' => 10,
        ]);

        $this->actingAs($this->cashier)
            ->post(route('sales.store'), $this->checkoutPayload([
                ['productId' => $product->id, 'unitId' => $strip->id, 'quantity' => 2, 'unitPrice' => 2800],
            ], 6000))
            ->assertRedirect(route('sales.pos'));

        $balance->refresh();
        $ledger = StockLedger::where('type', StockLedger::TYPE_SALE)->firstOrFail();

        $this->assertSame(8.0, (float) $balance->quantity);
        $this->assertSame(StockLedger::DIRECTION_OUT, $ledger->direction);
        $this->assertSame($strip->id, $ledger->product_unit_id);
        $this->assertSame(2.0, (float) $ledger->quantity);
        $this->assertSame(Sale::class, $ledger->reference_type);
    }

    public function test_integer_unpack_checkout_deducts_whole_parent_units(): void
    {
        [$product, $box, $strip, $pill] = $this->createProductWithUnits();

        $boxBalance = StockBalance::create([
            'product_id' => $product->id,
            'product_unit_id' => $box->id,
            'quantity' => 1,
        ]);

        $this->actingAs($this->cashier)
            ->post(route('sales.store'), $this->checkoutPayload([
                ['productId' => $product->id, 'unitId' => $pill->id, 'quantity' => 5, 'unitPrice' => 300],
            ], 2000))
            ->assertRedirect(route('sales.pos'));

        $boxBalance->refresh();
        $saleLine = Sale::firstOrFail()->lines()->firstOrFail();

        $this->assertSame($pill->id, $saleLine->product_unit_id);
        $this->assertSame(5.0, (float) $saleLine->quantity);
        $this->assertSame(0.0, (float) $boxBalance->quantity);

        $unpackOuts = StockLedger::where('type', StockLedger::TYPE_UNIT_UNPACK_OUT)->get();

        $this->assertSame(2, $unpackOuts->count());
        $this->assertTrue($unpackOuts->every(fn (StockLedger $ledger): bool => (float) $ledger->quantity === 1.0));

        $stripBalance = StockBalance::query()
            ->where('product_id', $product->id)
            ->where('product_unit_id', $strip->id)
            ->first();

        $this->assertNotNull($stripBalance);
        $this->assertSame(9.0, (float) $stripBalance->quantity);

        $pillBalance = StockBalance::query()
            ->where('product_id', $product->id)
            ->where('product_unit_id', $pill->id)
            ->first();

        $this->assertNotNull($pillBalance);
        $this->assertSame(5.0, (float) $pillBalance->quantity);
    }

    public function test_checkout_can_split_deduction_across_direct_stock_and_integer_unpack(): void
    {
        [$product, $box, $strip, $pill] = $this->createProductWithUnits();

        $stripBalance = StockBalance::create([
            'product_id' => $product->id,
            'product_unit_id' => $strip->id,
            'quantity' => 2,
        ]);
        $boxBalance = StockBalance::create([
            'product_id' => $product->id,
            'product_unit_id' => $box->id,
            'quantity' => 1,
        ]);

        $this->actingAs($this->cashier)
            ->post(route('sales.store'), $this->checkoutPayload([
                ['productId' => $product->id, 'unitId' => $pill->id, 'quantity' => 30, 'unitPrice' => 300],
            ], 10000))
            ->assertRedirect(route('sales.pos'));

        $stripBalance->refresh();
        $boxBalance->refresh();

        $this->assertSame(9.0, (float) $stripBalance->quantity);
        $this->assertSame(0.0, (float) $boxBalance->quantity);

        $saleLedgers = StockLedger::where('type', StockLedger::TYPE_SALE)->orderBy('id')->get();

        $this->assertTrue($saleLedgers->every(fn (StockLedger $ledger): bool => (float) $ledger->quantity === floor((float) $ledger->quantity)));
        $this->assertSame(30.0, (float) $saleLedgers->sum('quantity'));
    }

    public function test_checkout_rejects_insufficient_stock_without_mutating_database(): void
    {
        [$product, $box, , $pill] = $this->createProductWithUnits();

        $balance = StockBalance::create([
            'product_id' => $product->id,
            'product_unit_id' => $box->id,
            'quantity' => 0.01,
        ]);

        $this->actingAs($this->cashier)
            ->from(route('sales.pos'))
            ->post(route('sales.store'), $this->checkoutPayload([
                ['productId' => $product->id, 'unitId' => $pill->id, 'quantity' => 5, 'unitPrice' => 300],
            ], 2000))
            ->assertRedirect(route('sales.pos'))
            ->assertSessionHasErrors('checkout');

        $balance->refresh();

        $this->assertSame(0.01, (float) $balance->quantity);
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_lines', 0);
        $this->assertDatabaseCount('stock_ledgers', 0);
    }

    public function test_checkout_persists_direct_sale_allocations_after_auto_unpack(): void
    {
        [$product, $box, , $pill] = $this->createProductWithUnits();

        StockBalance::create([
            'product_id' => $product->id,
            'product_unit_id' => $box->id,
            'quantity' => 1,
        ]);

        $this->actingAs($this->cashier)
            ->post(route('sales.store'), $this->checkoutPayload([
                ['productId' => $product->id, 'unitId' => $pill->id, 'quantity' => 10, 'unitPrice' => 300],
            ], 5000))
            ->assertRedirect(route('sales.pos'));

        /** @var \App\Models\SaleLine $saleLine */
        $saleLine = Sale::query()->with('lines.stockAllocations')->firstOrFail()->lines->firstOrFail();
        $allocations = $saleLine->stockAllocations;

        $this->assertGreaterThan(0, $allocations->count());
        $this->assertTrue($allocations->every(fn ($allocation): bool => $allocation->allocation_type === 'direct'));
        $this->assertSame($pill->id, $allocations->first()->product_unit_id);
        $this->assertSame(10.0, (float) $allocations->sum('sale_unit_quantity'));
    }

    /**
     * @return array{Product, ProductUnit, ProductUnit, ProductUnit}
     */
    private function createProductWithUnits(): array
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
        $box = ProductUnit::create([
            'product_id' => $product->id,
            'name' => 'Box',
            'abbreviation' => 'box',
            'level' => 1,
            'is_purchase_unit' => true,
            'is_sale_unit' => true,
            'sale_price' => 25000,
        ]);
        $strip = ProductUnit::create([
            'product_id' => $product->id,
            'parent_product_unit_id' => $box->id,
            'name' => 'Strip',
            'abbreviation' => 'strip',
            'level' => 2,
            'conversion_factor' => 10,
            'is_purchase_unit' => true,
            'is_sale_unit' => true,
            'sale_price' => 2800,
        ]);
        $pill = ProductUnit::create([
            'product_id' => $product->id,
            'parent_product_unit_id' => $strip->id,
            'name' => 'Pill',
            'abbreviation' => 'pill',
            'level' => 3,
            'conversion_factor' => 10,
            'is_purchase_unit' => false,
            'is_sale_unit' => true,
            'sale_price' => 300,
        ]);

        return [$product, $box, $strip, $pill];
    }

    /**
     * @param  array<int, array<string, mixed>>  $cartLines
     * @return array<string, mixed>
     */
    private function checkoutPayload(array $cartLines, float $amountPaid): array
    {
        return [
            'cart_payload' => json_encode($cartLines),
            'discount_total' => 0,
            'tax_total' => 0,
            'amount_paid' => $amountPaid,
            'payment_method' => Sale::PAYMENT_CASH,
        ];
    }
}
