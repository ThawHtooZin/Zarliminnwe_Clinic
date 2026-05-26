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

class HoldResumeSaleTest extends TestCase
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

    public function test_cashier_can_hold_cart_without_deducting_stock(): void
    {
        [$product, , $strip] = $this->createProductWithUnits();
        $balance = StockBalance::create([
            'product_id' => $product->id,
            'product_unit_id' => $strip->id,
            'quantity' => 10,
        ]);

        $this->actingAs($this->cashier)
            ->post(route('sales.hold'), $this->payload([
                ['productId' => $product->id, 'unitId' => $strip->id, 'quantity' => 2, 'unitPrice' => 2800],
            ]))
            ->assertRedirect(route('sales.pos'))
            ->assertSessionHas('status');

        $sale = Sale::with('lines')->firstOrFail();
        $balance->refresh();

        $this->assertSame(Sale::STATUS_HELD, $sale->status);
        $this->assertSame($this->cashier->id, $sale->sold_by);
        $this->assertSame(1, $sale->lines->count());
        $this->assertSame($strip->id, $sale->lines->first()->product_unit_id);
        $this->assertSame(10.0, (float) $balance->quantity);
        $this->assertDatabaseCount('stock_ledgers', 0);
    }

    public function test_cashier_can_resume_held_sale_into_pos(): void
    {
        [$product, , $strip] = $this->createProductWithUnits();
        $sale = $this->createHeldSale($product, $strip);

        $this->actingAs($this->cashier)
            ->get(route('sales.resume', $sale))
            ->assertOk()
            ->assertSee('Resuming held sale '.$sale->sale_number)
            ->assertSee('Paracetamol 500mg')
            ->assertSee((string) $sale->id);
    }

    public function test_completing_resumed_sale_deducts_stock_at_completion_time(): void
    {
        [$product, , $strip] = $this->createProductWithUnits();
        $sale = $this->createHeldSale($product, $strip);

        $this->assertDatabaseCount('stock_ledgers', 0);

        $balance = StockBalance::create([
            'product_id' => $product->id,
            'product_unit_id' => $strip->id,
            'quantity' => 10,
        ]);

        $this->actingAs($this->cashier)
            ->post(route('sales.store'), $this->payload([
                ['productId' => $product->id, 'unitId' => $strip->id, 'quantity' => 2, 'unitPrice' => 2800],
            ], [
                'held_sale_id' => $sale->id,
                'amount_paid' => 6000,
            ]))
            ->assertRedirect(route('sales.pos'));

        $sale->refresh();
        $balance->refresh();

        $this->assertSame(Sale::STATUS_COMPLETED, $sale->status);
        $this->assertSame(8.0, (float) $balance->quantity);
        $this->assertDatabaseHas('stock_ledgers', [
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
            'type' => StockLedger::TYPE_SALE,
            'direction' => StockLedger::DIRECTION_OUT,
        ]);
    }

    public function test_resumed_sale_fails_if_stock_is_insufficient_at_completion_time(): void
    {
        [$product, $box, , $pill] = $this->createProductWithUnits();
        $sale = $this->createHeldSale($product, $pill, 5, 300);
        $balance = StockBalance::create([
            'product_id' => $product->id,
            'product_unit_id' => $box->id,
            'quantity' => 0.01,
        ]);

        $this->actingAs($this->cashier)
            ->from(route('sales.resume', $sale))
            ->post(route('sales.store'), $this->payload([
                ['productId' => $product->id, 'unitId' => $pill->id, 'quantity' => 5, 'unitPrice' => 300],
            ], [
                'held_sale_id' => $sale->id,
                'amount_paid' => 2000,
            ]))
            ->assertRedirect(route('sales.resume', $sale))
            ->assertSessionHasErrors('checkout');

        $sale->refresh();
        $balance->refresh();

        $this->assertSame(Sale::STATUS_HELD, $sale->status);
        $this->assertSame(0.01, (float) $balance->quantity);
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

    private function createHeldSale(Product $product, ProductUnit $unit, float $quantity = 2, float $unitPrice = 2800): Sale
    {
        $sale = Sale::create([
            'sale_number' => 'S-20260526-0001',
            'status' => Sale::STATUS_HELD,
            'subtotal' => $quantity * $unitPrice,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => $quantity * $unitPrice,
            'amount_paid' => 0,
            'change_amount' => 0,
            'payment_method' => Sale::PAYMENT_CASH,
            'sold_by' => $this->cashier->id,
        ]);

        $sale->lines()->create([
            'product_id' => $product->id,
            'product_unit_id' => $unit->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'line_total' => $quantity * $unitPrice,
        ]);

        return $sale;
    }

    /**
     * @param  array<int, array<string, mixed>>  $cartLines
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $cartLines, array $overrides = []): array
    {
        return array_replace([
            'cart_payload' => json_encode($cartLines),
            'discount_total' => 0,
            'tax_total' => 0,
            'amount_paid' => 0,
            'payment_method' => Sale::PAYMENT_CASH,
        ], $overrides);
    }
}
