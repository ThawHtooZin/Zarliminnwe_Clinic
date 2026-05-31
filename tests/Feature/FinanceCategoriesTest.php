<?php

namespace Tests\Feature;

use App\Models\ExpenseCategory;
use App\Models\IncomeCategory;
use App\Models\User;
use Database\Seeders\ExpenseCategorySeeder;
use Database\Seeders\IncomeCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FinanceCategoriesTest extends TestCase
{
    use RefreshDatabase;

    private User $pharmacist;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->pharmacist = User::factory()->create([
            'role' => User::ROLE_PHARMACIST,
        ]);
    }

    public function test_income_categories_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('income_categories', [
            'id',
            'name',
            'type',
            'description',
            'is_active',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_expense_categories_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('expense_categories', [
            'id',
            'name',
            'description',
            'is_active',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_pharmacist_can_create_income_category_and_action_is_audited(): void
    {
        $this->actingAs($this->pharmacist)
            ->post(route('finance.income-categories.store'), [
                'name' => 'Consultation Fee',
                'type' => IncomeCategory::TYPE_SERVICE,
                'description' => 'Patient consultation.',
                'is_active' => 1,
            ])
            ->assertRedirect(route('finance.income-categories.index'));

        $category = IncomeCategory::firstOrFail();

        $this->assertSame('Consultation Fee', $category->name);
        $this->assertSame(IncomeCategory::TYPE_SERVICE, $category->type);
        $this->assertTrue($category->is_active);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->pharmacist->id,
            'action' => 'income_category.created',
            'auditable_type' => IncomeCategory::class,
            'auditable_id' => $category->id,
        ]);
    }

    public function test_income_category_name_must_be_unique(): void
    {
        IncomeCategory::create([
            'name' => 'Consultation Fee',
            'type' => IncomeCategory::TYPE_SERVICE,
            'is_active' => true,
        ]);

        $this->actingAs($this->pharmacist)
            ->from(route('finance.income-categories.create'))
            ->post(route('finance.income-categories.store'), [
                'name' => 'Consultation Fee',
                'type' => IncomeCategory::TYPE_GENERAL,
                'is_active' => 1,
            ])
            ->assertRedirect(route('finance.income-categories.create'))
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('income_categories', 1);
    }

    public function test_pharmacist_can_update_expense_category(): void
    {
        $category = ExpenseCategory::create([
            'name' => 'Rent',
            'description' => 'Monthly rent.',
            'is_active' => true,
        ]);

        $this->actingAs($this->pharmacist)
            ->put(route('finance.expense-categories.update', $category), [
                'name' => 'Office Rent',
                'description' => 'Updated rent category.',
                'is_active' => 0,
            ])
            ->assertRedirect(route('finance.expense-categories.index'));

        $category->refresh();

        $this->assertSame('Office Rent', $category->name);
        $this->assertFalse($category->is_active);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->pharmacist->id,
            'action' => 'expense_category.updated',
            'auditable_type' => ExpenseCategory::class,
            'auditable_id' => $category->id,
        ]);
    }

    public function test_active_scope_returns_only_active_categories(): void
    {
        IncomeCategory::create([
            'name' => 'Active Service',
            'type' => IncomeCategory::TYPE_SERVICE,
            'is_active' => true,
        ]);
        IncomeCategory::create([
            'name' => 'Inactive Service',
            'type' => IncomeCategory::TYPE_SERVICE,
            'is_active' => false,
        ]);

        ExpenseCategory::create([
            'name' => 'Active Expense',
            'is_active' => true,
        ]);
        ExpenseCategory::create([
            'name' => 'Inactive Expense',
            'is_active' => false,
        ]);

        $this->assertCount(1, IncomeCategory::active()->get());
        $this->assertCount(1, ExpenseCategory::active()->get());
    }

    public function test_income_category_seeders_create_default_categories(): void
    {
        $this->seed(IncomeCategorySeeder::class);

        $this->assertDatabaseHas('income_categories', ['name' => 'Consultation Fee', 'type' => 'service']);
        $this->assertDatabaseHas('income_categories', ['name' => 'Other Income', 'type' => 'general']);
    }

    public function test_expense_category_seeder_creates_default_categories(): void
    {
        $this->seed(ExpenseCategorySeeder::class);

        $this->assertDatabaseHas('expense_categories', ['name' => 'Rent']);
        $this->assertDatabaseHas('expense_categories', ['name' => 'Other Expense']);
    }

    public function test_guest_and_stock_manager_cannot_manage_finance_categories(): void
    {
        $this->get(route('finance.income-categories.index'))->assertRedirect(route('login'));

        $stockManager = User::factory()->create(['role' => User::ROLE_STOCK_MANAGER]);

        $this->actingAs($stockManager)
            ->get(route('finance.income-categories.index'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'You do not have access to this page.');

        $this->actingAs($stockManager)
            ->get(route('finance.expense-categories.index'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'You do not have access to this page.');
    }
}
