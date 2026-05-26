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

class SaleVoidTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $pharmacist;

    private User $cashier;

    private Product $product;

    private ProductUnit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
        $this->pharmacist = User::factory()->create([
            'role' => User::ROLE_PHARMACIST,
        ]);
        $this->cashier = User::factory()->create([
            'role' => User::ROLE_CASHIER,
        ]);

        $category = ProductCategory::create([
            'name' => 'Medicines',
            'is_active' => true,
        ]);
        $this->product = Product::create([
            'product_category_id' => $category->id,
            'name' => 'Paracetamol 500mg',
            'sku' => 'PARA-500',
            'is_active' => true,
        ]);
        $this->unit = ProductUnit::create([
            'product_id' => $this->product->id,
            'name' => 'Strip',
            'abbreviation' => 'strip',
            'level' => 1,
            'is_purchase_unit' => true,
            'is_sale_unit' => true,
            'sale_price' => 2800,
        ]);
    }

    public function test_admin_can_void_completed_sale_and_restore_stock(): void
    {
        [$sale, $balance, $originalLedger] = $this->completedSaleWithStockMovement();

        $this->actingAs($this->admin)
            ->from(route('sales.show', $sale))
            ->post(route('sales.void', $sale), [
                'void_reason' => 'Customer returned wrong medicine.',
            ])
            ->assertRedirect(route('sales.show', $sale));

        $sale->refresh();
        $balance->refresh();
        $originalLedger->refresh();

        $voidLedger = StockLedger::where('type', StockLedger::TYPE_SALE_VOID)->firstOrFail();

        $this->assertSame(Sale::STATUS_VOIDED, $sale->status);
        $this->assertSame($this->admin->id, $sale->voided_by);
        $this->assertNotNull($sale->voided_at);
        $this->assertSame('Customer returned wrong medicine.', $sale->void_reason);
        $this->assertSame(10.0, (float) $balance->quantity);

        $this->assertSame(StockLedger::TYPE_SALE, $originalLedger->type);
        $this->assertSame(StockLedger::DIRECTION_OUT, $originalLedger->direction);
        $this->assertSame(2.0, (float) $originalLedger->quantity);

        $this->assertSame(StockLedger::DIRECTION_IN, $voidLedger->direction);
        $this->assertSame($this->product->id, $voidLedger->product_id);
        $this->assertSame($this->unit->id, $voidLedger->product_unit_id);
        $this->assertSame(2.0, (float) $voidLedger->quantity);
        $this->assertSame(Sale::class, $voidLedger->reference_type);
        $this->assertSame($sale->id, $voidLedger->reference_id);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'sale.voided',
            'auditable_type' => Sale::class,
            'auditable_id' => $sale->id,
        ]);
    }

    public function test_pharmacist_can_void_completed_sale(): void
    {
        [$sale] = $this->completedSaleWithStockMovement();

        $this->actingAs($this->pharmacist)
            ->post(route('sales.void', $sale), [
                'void_reason' => 'Pharmacist approved correction.',
            ])
            ->assertRedirect(route('sales.show', $sale));

        $this->assertSame(Sale::STATUS_VOIDED, $sale->fresh()->status);
    }

    public function test_cashier_cannot_void_sale_or_see_void_ui(): void
    {
        [$sale] = $this->completedSaleWithStockMovement();

        $this->actingAs($this->cashier)
            ->get(route('sales.show', $sale))
            ->assertOk()
            ->assertDontSee('Void Sale');

        $this->actingAs($this->cashier)
            ->post(route('sales.void', $sale), [
                'void_reason' => 'Cashier should not be allowed.',
            ])
            ->assertForbidden();

        $this->assertSame(Sale::STATUS_COMPLETED, $sale->fresh()->status);
        $this->assertDatabaseMissing('stock_ledgers', [
            'type' => StockLedger::TYPE_SALE_VOID,
            'reference_id' => $sale->id,
        ]);
    }

    public function test_void_reason_is_required(): void
    {
        [$sale] = $this->completedSaleWithStockMovement();

        $this->actingAs($this->admin)
            ->from(route('sales.show', $sale))
            ->post(route('sales.void', $sale), [
                'void_reason' => '',
            ])
            ->assertRedirect(route('sales.show', $sale))
            ->assertSessionHasErrors('void_reason');

        $this->assertSame(Sale::STATUS_COMPLETED, $sale->fresh()->status);
        $this->assertDatabaseMissing('stock_ledgers', [
            'type' => StockLedger::TYPE_SALE_VOID,
            'reference_id' => $sale->id,
        ]);
    }

    public function test_double_void_is_rejected(): void
    {
        [$sale, $balance] = $this->completedSaleWithStockMovement();

        $this->actingAs($this->admin)
            ->post(route('sales.void', $sale), [
                'void_reason' => 'First void.',
            ])
            ->assertRedirect(route('sales.show', $sale));

        $this->actingAs($this->admin)
            ->from(route('sales.show', $sale))
            ->post(route('sales.void', $sale), [
                'void_reason' => 'Second void.',
            ])
            ->assertRedirect(route('sales.show', $sale))
            ->assertSessionHasErrors('void');

        $balance->refresh();

        $this->assertSame(Sale::STATUS_VOIDED, $sale->fresh()->status);
        $this->assertSame(10.0, (float) $balance->quantity);
        $this->assertSame(1, StockLedger::where('type', StockLedger::TYPE_SALE_VOID)->count());
    }

    public function test_sales_list_clearly_shows_voided_status(): void
    {
        [$sale] = $this->completedSaleWithStockMovement();

        $this->actingAs($this->admin)
            ->post(route('sales.void', $sale), [
                'void_reason' => 'Display voided status.',
            ]);

        $this->actingAs($this->cashier)
            ->get(route('sales.index'))
            ->assertOk()
            ->assertSee($sale->sale_number)
            ->assertSee('voided');
    }

    /**
     * @return array{Sale, StockBalance, StockLedger}
     */
    private function completedSaleWithStockMovement(): array
    {
        $sale = Sale::create([
            'sale_number' => 'S-20260526-0001',
            'status' => Sale::STATUS_COMPLETED,
            'subtotal' => 5600,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 5600,
            'amount_paid' => 6000,
            'change_amount' => 400,
            'payment_method' => Sale::PAYMENT_CASH,
            'sold_by' => $this->cashier->id,
            'sold_at' => now(),
        ]);

        $sale->lines()->create([
            'product_id' => $this->product->id,
            'product_unit_id' => $this->unit->id,
            'quantity' => 2,
            'unit_price' => 2800,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'line_total' => 5600,
        ]);

        $balance = StockBalance::create([
            'product_id' => $this->product->id,
            'product_unit_id' => $this->unit->id,
            'quantity' => 8,
        ]);

        $ledger = StockLedger::create([
            'product_id' => $this->product->id,
            'product_unit_id' => $this->unit->id,
            'type' => StockLedger::TYPE_SALE,
            'direction' => StockLedger::DIRECTION_OUT,
            'quantity' => 2,
            'unit_cost' => 0,
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
            'reason' => 'Sale '.$sale->sale_number,
            'created_by' => $this->cashier->id,
        ]);

        return [$sale, $balance, $ledger];
    }
}
