<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ExpenseCategory;
use App\Models\ExpenseEntry;
use App\Models\IncomeCategory;
use App\Models\IncomeEntry;
use App\Models\PatientVisit;
use App\Models\User;
use Database\Seeders\ExpenseCategorySeeder;
use Database\Seeders\IncomeCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseFourAuditAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $pharmacist;

    private User $cashier;

    private User $stockManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->pharmacist = User::factory()->create(['role' => User::ROLE_PHARMACIST]);
        $this->cashier = User::factory()->create(['role' => User::ROLE_CASHIER]);
        $this->stockManager = User::factory()->create(['role' => User::ROLE_STOCK_MANAGER]);

        $this->seed(IncomeCategorySeeder::class);
        $this->seed(ExpenseCategorySeeder::class);
    }

    public function test_guest_cannot_access_phase_four_routes(): void
    {
        foreach ($this->phaseFourRoutes() as $route) {
            $this->get($route)->assertRedirect(route('login'));
        }
    }

    public function test_stock_manager_cannot_access_phase_four_routes(): void
    {
        foreach ($this->phaseFourRoutes() as $route) {
            $this->actingAs($this->stockManager)
                ->get($route)
                ->assertForbidden();
        }
    }

    public function test_cashier_can_manage_patient_visits_income_and_expenses(): void
    {
        $this->actingAs($this->cashier)
            ->post(route('patient-visits.store'), [
                'patient_name' => 'U Kyaw',
                'age' => 40,
                'visited_at' => '2026-05-26 09:00:00',
            ])
            ->assertRedirect();

        $serviceCategory = IncomeCategory::where('name', 'Consultation Fee')->firstOrFail();
        $expenseCategory = ExpenseCategory::where('name', 'Rent')->firstOrFail();

        $this->actingAs($this->cashier)
            ->post(route('finance.income.store'), [
                'income_category_id' => $serviceCategory->id,
                'amount' => 3000,
                'payment_method' => IncomeEntry::PAYMENT_CASH,
                'received_at' => '2026-05-26 10:00:00',
            ])
            ->assertRedirect(route('finance.income.index'));

        $this->actingAs($this->cashier)
            ->post(route('finance.expenses.store'), [
                'expense_category_id' => $expenseCategory->id,
                'amount' => 1000,
                'expense_date' => '2026-05-26',
                'payment_method' => ExpenseEntry::PAYMENT_CASH,
            ])
            ->assertRedirect(route('finance.expenses.index'));

        $this->assertDatabaseCount('patient_visits', 1);
        $this->assertDatabaseCount('income_entries', 1);
        $this->assertDatabaseCount('expense_entries', 1);
    }

    public function test_cashier_cannot_manage_finance_categories_or_finance_summary(): void
    {
        $this->actingAs($this->cashier)
            ->get(route('finance.income-categories.index'))
            ->assertForbidden();

        $this->actingAs($this->cashier)
            ->get(route('finance.expense-categories.index'))
            ->assertForbidden();

        $this->actingAs($this->cashier)
            ->get(route('reports.finance-summary'))
            ->assertForbidden();
    }

    public function test_pharmacist_can_access_finance_summary_and_category_management(): void
    {
        $this->actingAs($this->pharmacist)
            ->get(route('reports.finance-summary'))
            ->assertOk();

        $this->actingAs($this->pharmacist)
            ->get(route('finance.income-categories.index'))
            ->assertOk();

        $this->actingAs($this->pharmacist)
            ->get(route('finance.expense-categories.index'))
            ->assertOk();
    }

    public function test_income_entry_update_logs_amount_change_in_audit_details(): void
    {
        $category = IncomeCategory::where('name', 'Consultation Fee')->firstOrFail();
        $entry = IncomeEntry::create([
            'income_category_id' => $category->id,
            'amount' => 2000,
            'payment_method' => IncomeEntry::PAYMENT_CASH,
            'received_at' => '2026-05-26 10:00:00',
            'received_by' => $this->cashier->id,
        ]);

        $this->actingAs($this->cashier)
            ->put(route('finance.income.update', $entry), [
                'income_category_id' => $category->id,
                'amount' => 4500,
                'payment_method' => IncomeEntry::PAYMENT_CASH,
                'received_at' => '2026-05-26 11:00:00',
            ])
            ->assertRedirect(route('finance.income.index'));

        $audit = AuditLog::query()
            ->where('action', 'income_entry.updated')
            ->where('auditable_id', $entry->id)
            ->firstOrFail();

        $this->assertSame(2000, (int) $audit->old_values['amount']);
        $this->assertSame(4500, (int) $audit->new_values['amount']);
    }

    public function test_expense_entry_update_logs_amount_change_in_audit_details(): void
    {
        $category = ExpenseCategory::where('name', 'Utilities')->firstOrFail();
        $entry = ExpenseEntry::create([
            'expense_category_id' => $category->id,
            'amount' => 3000,
            'expense_date' => '2026-05-26',
            'payment_method' => ExpenseEntry::PAYMENT_CASH,
            'created_by' => $this->cashier->id,
        ]);

        $this->actingAs($this->cashier)
            ->put(route('finance.expenses.update', $entry), [
                'expense_category_id' => $category->id,
                'amount' => 8000,
                'expense_date' => '2026-05-27',
                'payment_method' => ExpenseEntry::PAYMENT_CASH,
            ])
            ->assertRedirect(route('finance.expenses.index'));

        $audit = AuditLog::query()
            ->where('action', 'expense_entry.updated')
            ->where('auditable_id', $entry->id)
            ->firstOrFail();

        $this->assertSame(3000, (int) $audit->old_values['amount']);
        $this->assertSame(8000, (int) $audit->new_values['amount']);
    }

    public function test_income_and_expense_category_changes_are_audited(): void
    {
        $incomeCategory = IncomeCategory::where('name', 'Other Income')->firstOrFail();
        $expenseCategory = ExpenseCategory::where('name', 'Other Expense')->firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('finance.income-categories.update', $incomeCategory), [
                'name' => 'Other Income',
                'type' => IncomeCategory::TYPE_GENERAL,
                'description' => 'Updated description',
                'is_active' => true,
            ])
            ->assertRedirect(route('finance.income-categories.index'));

        $this->actingAs($this->admin)
            ->put(route('finance.expense-categories.update', $expenseCategory), [
                'name' => 'Other Expense',
                'description' => 'Updated expense description',
                'is_active' => true,
            ])
            ->assertRedirect(route('finance.expense-categories.index'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'income_category.updated',
            'auditable_type' => IncomeCategory::class,
            'auditable_id' => $incomeCategory->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'expense_category.updated',
            'auditable_type' => ExpenseCategory::class,
            'auditable_id' => $expenseCategory->id,
        ]);
    }

    public function test_patient_visit_update_is_audited_with_allowed_fields_only(): void
    {
        $visit = PatientVisit::create([
            'patient_name' => 'Ma Hla',
            'age' => 30,
            'visited_at' => '2026-05-26 09:00:00',
            'created_by' => $this->cashier->id,
        ]);

        $this->actingAs($this->cashier)
            ->from(route('patient-visits.edit', $visit))
            ->put(route('patient-visits.update', $visit), [
                'patient_name' => 'Ma Hla Updated',
                'age' => 31,
                'visited_at' => '2026-05-27 09:00:00',
                'diagnosis' => 'Should not save',
            ])
            ->assertRedirect(route('patient-visits.edit', $visit))
            ->assertSessionHasErrors('diagnosis');

        $this->actingAs($this->cashier)
            ->put(route('patient-visits.update', $visit), [
                'patient_name' => 'Ma Hla Updated',
                'age' => 31,
                'visited_at' => '2026-05-27 09:00:00',
            ])
            ->assertRedirect(route('patient-visits.show', $visit));

        $audit = AuditLog::query()
            ->where('action', 'patient_visit.updated')
            ->where('auditable_id', $visit->id)
            ->firstOrFail();

        $this->assertSame('Ma Hla', $audit->old_values['patient_name']);
        $this->assertSame('Ma Hla Updated', $audit->new_values['patient_name']);
        $this->assertArrayNotHasKey('diagnosis', $audit->new_values);
    }

    /**
     * @return array<int, string>
     */
    private function phaseFourRoutes(): array
    {
        return [
            route('patient-visits.index'),
            route('patient-visits.create'),
            route('finance.income.index'),
            route('finance.income.create'),
            route('finance.expenses.index'),
            route('finance.expenses.create'),
            route('finance.income-categories.index'),
            route('finance.expense-categories.index'),
            route('reports.finance-summary'),
            route('reports.finance-income'),
            route('reports.finance-expenses'),
        ];
    }
}
