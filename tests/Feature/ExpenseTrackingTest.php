<?php

namespace Tests\Feature;

use App\Models\ExpenseCategory;
use App\Models\ExpenseEntry;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\User;
use Database\Seeders\ExpenseCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExpenseTrackingTest extends TestCase
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

        $this->seed(ExpenseCategorySeeder::class);
    }

    public function test_expense_entries_table_has_required_columns_and_no_inventory_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('expense_entries', [
            'id',
            'expense_category_id',
            'amount',
            'expense_date',
            'payee',
            'payment_method',
            'description',
            'created_by',
            'created_at',
            'updated_at',
        ]));

        $this->assertFalse(Schema::hasColumn('expense_entries', 'product_id'));
        $this->assertFalse(Schema::hasColumn('expense_entries', 'stock_balance_id'));
        $this->assertFalse(Schema::hasColumn('expense_entries', 'stock_ledger_id'));
        $this->assertFalse(Schema::hasColumn('expense_entries', 'purchase_receipt_id'));
        $this->assertFalse(Schema::hasColumn('expense_entries', 'sale_id'));
    }

    public function test_cashier_can_create_expense_entry_and_action_is_audited(): void
    {
        $category = ExpenseCategory::where('name', 'Rent')->firstOrFail();

        $this->actingAs($this->cashier)
            ->post(route('finance.expenses.store'), $this->validPayload($category))
            ->assertRedirect(route('finance.expenses.index'));

        $entry = ExpenseEntry::firstOrFail();

        $this->assertSame('Landlord Co', $entry->payee);
        $this->assertSame(150000.0, (float) $entry->amount);
        $this->assertSame($this->cashier->id, $entry->created_by);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'expense_entry.created',
            'auditable_type' => ExpenseEntry::class,
            'auditable_id' => $entry->id,
        ]);
    }

    public function test_cashier_can_update_expense_entry(): void
    {
        $category = ExpenseCategory::where('name', 'Utilities')->firstOrFail();
        $entry = ExpenseEntry::create([
            'expense_category_id' => $category->id,
            'amount' => 5000,
            'expense_date' => '2026-05-20',
            'payee' => 'Power Co',
            'payment_method' => ExpenseEntry::PAYMENT_CASH,
            'created_by' => $this->cashier->id,
        ]);

        $this->actingAs($this->cashier)
            ->put(route('finance.expenses.update', $entry), [
                'expense_category_id' => $category->id,
                'amount' => 7500,
                'expense_date' => '2026-05-21',
                'payee' => 'Power Co Updated',
                'payment_method' => ExpenseEntry::PAYMENT_CARD,
                'description' => 'Monthly bill',
            ])
            ->assertRedirect(route('finance.expenses.index'));

        $entry->refresh();

        $this->assertSame(7500.0, (float) $entry->amount);
        $this->assertSame('Power Co Updated', $entry->payee);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'expense_entry.updated',
            'auditable_id' => $entry->id,
        ]);
    }

    public function test_expense_create_rejects_inventory_related_fields(): void
    {
        $category = ExpenseCategory::where('name', 'Rent')->firstOrFail();

        $this->actingAs($this->cashier)
            ->from(route('finance.expenses.create'))
            ->post(route('finance.expenses.store'), $this->validPayload($category) + [
                'product_id' => 1,
                'stock_balance_id' => 1,
                'purchase_receipt_id' => 1,
            ])
            ->assertRedirect(route('finance.expenses.create'))
            ->assertSessionHasErrors(['product_id', 'stock_balance_id', 'purchase_receipt_id']);

        $this->assertDatabaseCount('expense_entries', 0);
    }

    public function test_inactive_expense_category_cannot_be_used(): void
    {
        $category = ExpenseCategory::where('name', 'Rent')->firstOrFail();
        $category->update(['is_active' => false]);

        $this->actingAs($this->cashier)
            ->from(route('finance.expenses.create'))
            ->post(route('finance.expenses.store'), $this->validPayload($category))
            ->assertRedirect(route('finance.expenses.create'))
            ->assertSessionHasErrors('form');

        $this->assertDatabaseCount('expense_entries', 0);
    }

    public function test_creating_expense_does_not_change_stock_ledger_or_balances(): void
    {
        [$product, $unit] = $this->createProductFixture();

        $balance = StockBalance::create([
            'product_id' => $product->id,
            'product_unit_id' => $unit->id,
            'quantity' => 10,
        ]);

        StockLedger::create([
            'product_id' => $product->id,
            'product_unit_id' => $unit->id,
            'type' => StockLedger::TYPE_OPENING_STOCK,
            'direction' => StockLedger::DIRECTION_IN,
            'quantity' => 10,
            'created_by' => $this->cashier->id,
        ]);

        $ledgerCountBefore = StockLedger::count();
        $balanceQuantityBefore = (float) $balance->fresh()->quantity;

        $category = ExpenseCategory::where('name', 'Supplies')->firstOrFail();

        $this->actingAs($this->cashier)
            ->post(route('finance.expenses.store'), $this->validPayload($category))
            ->assertRedirect(route('finance.expenses.index'));

        $this->assertSame($ledgerCountBefore, StockLedger::count());
        $this->assertSame($balanceQuantityBefore, (float) StockBalance::find($balance->id)->quantity);
        $this->assertDatabaseCount('expense_entries', 1);
    }

    public function test_expense_list_filters_by_date_category_and_payee(): void
    {
        $rent = ExpenseCategory::where('name', 'Rent')->firstOrFail();
        $utilities = ExpenseCategory::where('name', 'Utilities')->firstOrFail();

        ExpenseEntry::create([
            'expense_category_id' => $rent->id,
            'amount' => 100000,
            'expense_date' => '2026-05-26',
            'payee' => 'Landlord Co',
            'payment_method' => ExpenseEntry::PAYMENT_CASH,
            'created_by' => $this->cashier->id,
        ]);
        ExpenseEntry::create([
            'expense_category_id' => $utilities->id,
            'amount' => 5000,
            'expense_date' => '2026-05-10',
            'payee' => 'Power Co',
            'payment_method' => ExpenseEntry::PAYMENT_CASH,
            'created_by' => $this->cashier->id,
        ]);

        $this->actingAs($this->cashier)
            ->get(route('finance.expenses.index', [
                'expense_from' => '2026-05-26',
                'expense_to' => '2026-05-26',
                'expense_category_id' => $rent->id,
                'payee' => 'Landlord',
            ]))
            ->assertOk()
            ->assertSee('100,000.00')
            ->assertDontSee('5,000.00');
    }

    public function test_expense_form_does_not_show_product_or_stock_fields(): void
    {
        $response = $this->actingAs($this->cashier)
            ->get(route('finance.expenses.create'))
            ->assertOk()
            ->assertSee('Record Expense Entry')
            ->assertSee('do not change products');

        $content = $response->getContent();

        $this->assertStringNotContainsString('name="product_id"', $content);
        $this->assertStringNotContainsString('name="stock_balance_id"', $content);
        $this->assertStringNotContainsString('name="stock_ledger_id"', $content);
        $this->assertStringNotContainsString('name="purchase_receipt_id"', $content);
    }

    public function test_guest_and_stock_manager_cannot_access_expense_entries(): void
    {
        $this->get(route('finance.expenses.index'))->assertRedirect(route('login'));

        $stockManager = User::factory()->create(['role' => User::ROLE_STOCK_MANAGER]);

        $this->actingAs($stockManager)
            ->get(route('finance.expenses.index'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'You do not have access to this page.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(ExpenseCategory $category): array
    {
        return [
            'expense_category_id' => $category->id,
            'amount' => 150000,
            'expense_date' => '2026-05-26',
            'payee' => 'Landlord Co',
            'payment_method' => ExpenseEntry::PAYMENT_BANK_TRANSFER,
            'description' => 'Monthly rent payment.',
        ];
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
