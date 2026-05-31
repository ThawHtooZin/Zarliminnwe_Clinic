<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\StockLedger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesHistoryAndReceiptTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    private Product $product;

    private ProductUnit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->cashier = User::factory()->create([
            'name' => 'Clinic Cashier',
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

    public function test_sales_list_shows_sales_and_filters_by_status_and_date(): void
    {
        $completedSale = $this->createSale('S-20260526-0001', Sale::STATUS_COMPLETED, now()->setDate(2026, 5, 26));
        $heldSale = $this->createSale('S-20260525-0001', Sale::STATUS_HELD, null);
        $voidedSale = $this->createSale('S-20260524-0001', Sale::STATUS_VOIDED, now()->setDate(2026, 5, 24));

        $this->actingAs($this->cashier)
            ->get(route('sales.index'))
            ->assertOk()
            ->assertSee($completedSale->sale_number)
            ->assertSee($heldSale->sale_number)
            ->assertSee($voidedSale->sale_number)
            ->assertSee('Clinic Cashier')
            ->assertSee('No patient')
            ->assertSee('Details')
            ->assertSee('Receipt');

        $this->actingAs($this->cashier)
            ->get(route('sales.index', ['status' => Sale::STATUS_COMPLETED]))
            ->assertOk()
            ->assertSee($completedSale->sale_number)
            ->assertDontSee($heldSale->sale_number);

        $this->actingAs($this->cashier)
            ->get(route('sales.index', ['date' => '2026-05-24']))
            ->assertOk()
            ->assertSee($voidedSale->sale_number)
            ->assertDontSee($completedSale->sale_number);
    }

    public function test_sale_detail_shows_lines_payment_and_stock_movements(): void
    {
        $sale = $this->createSale('S-20260526-0002', Sale::STATUS_COMPLETED, now());
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

        $this->actingAs($this->cashier)
            ->get(route('sales.show', $sale))
            ->assertOk()
            ->assertSee($sale->sale_number)
            ->assertSee('Paracetamol 500mg')
            ->assertSee('Strip')
            ->assertSee('Payment Summary')
            ->assertSee('Stock Movements')
            ->assertSee('sale')
            ->assertSee('OUT')
            ->assertSee('2 strip');
    }

    public function test_receipt_view_shows_required_fields_and_print_css(): void
    {
        $sale = $this->createSale('S-20260526-0003', Sale::STATUS_COMPLETED, now());

        $this->actingAs($this->cashier)
            ->get(route('sales.receipt', $sale))
            ->assertOk()
            ->assertSee('ZARLI MIN NWE SPECIALIST CLINIC')
            ->assertSee($sale->sale_number)
            ->assertSee('Clinic Cashier')
            ->assertSee('Paracetamol 500mg')
            ->assertSee('Grand Total')
            ->assertSee('Payment Method')
            ->assertSee('Amount Paid')
            ->assertSee('Change')
            ->assertSee('Print Receipt')
            ->assertSee('@media print', false)
            ->assertSee('size: 80mm auto', false)
            ->assertSee('window.print()', false);
    }

    public function test_sale_detail_and_receipt_can_render_visit_patient_context(): void
    {
        $patient = Patient::factory()->create([
            'name' => 'Visit Patient',
            'age' => 33,
        ]);
        $visit = $patient->visitRecords()->create([
            'visited_at' => now(),
            'created_by' => $this->cashier->id,
        ]);

        $sale = $this->createSale('S-20260526-0010', Sale::STATUS_COMPLETED, now());
        $sale->update([
            'patient_visit_record_id' => $visit->id,
        ]);

        $this->actingAs($this->cashier)
            ->get(route('sales.show', $sale))
            ->assertOk()
            ->assertSee($patient->patient_code)
            ->assertSee('Visit Patient');

        $this->actingAs($this->cashier)
            ->get(route('sales.receipt', $sale))
            ->assertOk()
            ->assertSee('Visit Patient');
    }

    public function test_stock_manager_cannot_access_sales_history_or_receipt(): void
    {
        $stockManager = User::factory()->create([
            'role' => User::ROLE_STOCK_MANAGER,
        ]);
        $sale = $this->createSale('S-20260526-0004', Sale::STATUS_COMPLETED, now());

        $this->actingAs($stockManager)
            ->get(route('sales.index'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'You do not have access to this page.');

        $this->actingAs($stockManager)
            ->get(route('sales.show', $sale))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'You do not have access to this page.');

        $this->actingAs($stockManager)
            ->get(route('sales.receipt', $sale))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'You do not have access to this page.');
    }

    private function createSale(string $saleNumber, string $status, mixed $soldAt): Sale
    {
        $sale = Sale::create([
            'sale_number' => $saleNumber,
            'status' => $status,
            'subtotal' => 5600,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 5600,
            'amount_paid' => 6000,
            'change_amount' => 400,
            'payment_method' => Sale::PAYMENT_CASH,
            'sold_by' => $this->cashier->id,
            'sold_at' => $soldAt,
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

        return $sale;
    }
}
