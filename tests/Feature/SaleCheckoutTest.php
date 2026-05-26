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
        $this->assertNull($sale->patient_visit_id);
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

    public function test_fractional_checkout_deducts_from_larger_unit_balance(): void
    {
        [$product, $box, , $pill] = $this->createProductWithUnits();

        $balance = StockBalance::create([
            'product_id' => $product->id,
            'product_unit_id' => $box->id,
            'quantity' => 1,
        ]);

        $this->actingAs($this->cashier)
            ->post(route('sales.store'), $this->checkoutPayload([
                ['productId' => $product->id, 'unitId' => $pill->id, 'quantity' => 5, 'unitPrice' => 300],
            ], 2000))
            ->assertRedirect(route('sales.pos'));

        $balance->refresh();
        $ledger = StockLedger::where('type', StockLedger::TYPE_SALE)->firstOrFail();
        $saleLine = Sale::firstOrFail()->lines()->firstOrFail();

        $this->assertSame($pill->id, $saleLine->product_unit_id);
        $this->assertSame(5.0, (float) $saleLine->quantity);
        $this->assertSame(0.95, (float) $balance->quantity);
        $this->assertSame($box->id, $ledger->product_unit_id);
        $this->assertSame(0.05, (float) $ledger->quantity);
    }

    public function test_checkout_can_split_deduction_across_multiple_balances(): void
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
        $ledgers = StockLedger::where('type', StockLedger::TYPE_SALE)->orderBy('id')->get();

        $this->assertSame(0.0, (float) $stripBalance->quantity);
        $this->assertSame(0.9, (float) $boxBalance->quantity);
        $this->assertCount(2, $ledgers);
        $this->assertSame($strip->id, $ledgers[0]->product_unit_id);
        $this->assertSame(2.0, (float) $ledgers[0]->quantity);
        $this->assertSame($box->id, $ledgers[1]->product_unit_id);
        $this->assertSame(0.1, (float) $ledgers[1]->quantity);
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
