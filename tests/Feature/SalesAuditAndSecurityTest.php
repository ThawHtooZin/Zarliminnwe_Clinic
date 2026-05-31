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

class SalesAuditAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

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

    public function test_guest_cannot_access_pos(): void
    {
        $this->get(route('sales.pos'))
            ->assertRedirect(route('login'));
    }

    public function test_cashier_can_complete_sale_and_completion_is_audited(): void
    {
        StockBalance::create([
            'product_id' => $this->product->id,
            'product_unit_id' => $this->unit->id,
            'quantity' => 10,
        ]);

        $this->actingAs($this->cashier)
            ->post(route('sales.store'), $this->checkoutPayload())
            ->assertRedirect(route('sales.pos'));

        $sale = Sale::firstOrFail();

        $this->assertSame(Sale::STATUS_COMPLETED, $sale->status);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->cashier->id,
            'action' => 'sale.completed',
            'auditable_type' => Sale::class,
            'auditable_id' => $sale->id,
        ]);
    }

    public function test_sale_hold_is_audited(): void
    {
        $this->actingAs($this->cashier)
            ->post(route('sales.hold'), $this->holdPayload())
            ->assertRedirect(route('sales.pos'));

        $sale = Sale::firstOrFail();

        $this->assertSame(Sale::STATUS_HELD, $sale->status);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->cashier->id,
            'action' => 'sale.held',
            'auditable_type' => Sale::class,
            'auditable_id' => $sale->id,
        ]);
    }

    public function test_cashier_cannot_void_sale_but_admin_can_and_void_is_audited(): void
    {
        $sale = $this->completedSaleWithStockMovement();

        $this->actingAs($this->cashier)
            ->post(route('sales.void', $sale), [
                'void_reason' => 'Cashier attempted void.',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'You do not have access to this page.');

        $this->assertSame(Sale::STATUS_COMPLETED, $sale->fresh()->status);

        $this->actingAs($this->admin)
            ->post(route('sales.void', $sale), [
                'void_reason' => 'Admin approved correction.',
            ])
            ->assertRedirect(route('sales.show', $sale));

        $this->assertSame(Sale::STATUS_VOIDED, $sale->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->admin->id,
            'action' => 'sale.voided',
            'auditable_type' => Sale::class,
            'auditable_id' => $sale->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function checkoutPayload(): array
    {
        return [
            'cart_payload' => json_encode([
                [
                    'productId' => $this->product->id,
                    'unitId' => $this->unit->id,
                    'quantity' => 2,
                    'unitPrice' => 2800,
                ],
            ]),
            'discount_total' => 0,
            'tax_total' => 0,
            'amount_paid' => 6000,
            'payment_method' => Sale::PAYMENT_CASH,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function holdPayload(): array
    {
        return [
            'cart_payload' => json_encode([
                [
                    'productId' => $this->product->id,
                    'unitId' => $this->unit->id,
                    'quantity' => 2,
                    'unitPrice' => 2800,
                ],
            ]),
            'discount_total' => 0,
            'tax_total' => 0,
            'payment_method' => Sale::PAYMENT_CASH,
        ];
    }

    private function completedSaleWithStockMovement(): Sale
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

        StockBalance::create([
            'product_id' => $this->product->id,
            'product_unit_id' => $this->unit->id,
            'quantity' => 8,
        ]);

        StockLedger::create([
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

        return $sale;
    }
}
