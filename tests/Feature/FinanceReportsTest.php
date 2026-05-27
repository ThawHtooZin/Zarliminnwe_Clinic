<?php

namespace Tests\Feature;

use App\Domain\Finance\Services\FinanceSummaryService;
use App\Models\ExpenseCategory;
use App\Models\ExpenseEntry;
use App\Models\IncomeCategory;
use App\Models\IncomeEntry;
use App\Models\PatientVisit;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\ExpenseCategorySeeder;
use Database\Seeders\IncomeCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    private User $pharmacist;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->cashier = User::factory()->create([
            'role' => User::ROLE_CASHIER,
        ]);

        $this->pharmacist = User::factory()->create([
            'role' => User::ROLE_PHARMACIST,
        ]);

        $this->seed(IncomeCategorySeeder::class);
        $this->seed(ExpenseCategorySeeder::class);
    }

    public function test_finance_summary_aggregates_income_sources_and_net_balance(): void
    {
        $this->seedFinanceDataForSummary();

        $summary = app(FinanceSummaryService::class)->summarize([
            'date_from' => '2026-05-26',
            'date_to' => '2026-05-26',
        ]);

        $this->assertSame(3000.0, $summary['service_income']);
        $this->assertSame(1000.0, $summary['general_income']);
        $this->assertSame(5600.0, $summary['pharmacy_sales_income']);
        $this->assertSame(9600.0, $summary['total_income']);
        $this->assertSame(2000.0, $summary['expense_total']);
        $this->assertSame(7600.0, $summary['net_balance']);
    }

    public function test_finance_summary_excludes_voided_pharmacy_sales(): void
    {
        $this->seedFinanceDataForSummary();

        Sale::create([
            'sale_number' => 'S-VOID-001',
            'status' => Sale::STATUS_VOIDED,
            'subtotal' => 9999,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 9999,
            'amount_paid' => 9999,
            'change_amount' => 0,
            'payment_method' => Sale::PAYMENT_CASH,
            'sold_by' => $this->cashier->id,
            'sold_at' => '2026-05-26 12:00:00',
        ]);

        $summary = app(FinanceSummaryService::class)->summarize([
            'date_from' => '2026-05-26',
            'date_to' => '2026-05-26',
        ]);

        $this->assertSame(5600.0, $summary['pharmacy_sales_income']);
        $this->assertDatabaseCount('income_entries', 2);
    }

    public function test_pharmacy_sales_are_not_duplicated_into_income_entries(): void
    {
        $this->seedFinanceDataForSummary();

        $this->assertDatabaseCount('income_entries', 2);
        $this->assertDatabaseCount('sales', 1);
    }

    public function test_pharmacist_can_view_finance_summary_report(): void
    {
        $this->seedFinanceDataForSummary();

        $this->actingAs($this->pharmacist)
            ->get(route('reports.finance-summary', [
                'date_from' => '2026-05-26',
                'date_to' => '2026-05-26',
            ]))
            ->assertOk()
            ->assertSee('Finance Summary')
            ->assertSee('3,000.00')
            ->assertSee('5,600.00')
            ->assertSee('7,600.00')
            ->assertSee('Pharmacy POS sales are read separately');
    }

    public function test_cashier_can_access_income_and_expense_reports_but_not_finance_summary(): void
    {
        IncomeEntry::create([
            'income_category_id' => IncomeCategory::where('name', 'Consultation Fee')->firstOrFail()->id,
            'amount' => 1500,
            'payment_method' => IncomeEntry::PAYMENT_CASH,
            'received_at' => '2026-05-26 10:00:00',
            'received_by' => $this->cashier->id,
        ]);

        ExpenseEntry::create([
            'expense_category_id' => ExpenseCategory::where('name', 'Rent')->firstOrFail()->id,
            'amount' => 500,
            'expense_date' => '2026-05-26',
            'payment_method' => ExpenseEntry::PAYMENT_CASH,
            'created_by' => $this->cashier->id,
        ]);

        $this->actingAs($this->cashier)
            ->get(route('reports.finance-income', [
                'date_from' => '2026-05-26',
                'date_to' => '2026-05-26',
            ]))
            ->assertOk()
            ->assertSee('1,500.00');

        $this->actingAs($this->cashier)
            ->get(route('reports.finance-expenses', [
                'date_from' => '2026-05-26',
                'date_to' => '2026-05-26',
            ]))
            ->assertOk()
            ->assertSee('500.00');

        $this->actingAs($this->cashier)
            ->get(route('reports.finance-summary'))
            ->assertForbidden();
    }

    public function test_income_report_filters_by_date_category_and_patient_visit(): void
    {
        $visit = PatientVisit::create([
            'patient_name' => 'Ma Hla',
            'age' => 30,
            'visited_at' => '2026-05-26 09:00:00',
            'created_by' => $this->cashier->id,
        ]);

        $serviceCategory = IncomeCategory::where('name', 'Consultation Fee')->firstOrFail();
        $generalCategory = IncomeCategory::where('name', 'Other Income')->firstOrFail();

        IncomeEntry::create([
            'income_category_id' => $serviceCategory->id,
            'patient_visit_id' => $visit->id,
            'amount' => 4000,
            'payment_method' => IncomeEntry::PAYMENT_CASH,
            'received_at' => '2026-05-26 11:00:00',
            'received_by' => $this->cashier->id,
        ]);

        IncomeEntry::create([
            'income_category_id' => $generalCategory->id,
            'amount' => 900,
            'payment_method' => IncomeEntry::PAYMENT_CASH,
            'received_at' => '2026-05-20 11:00:00',
            'received_by' => $this->cashier->id,
        ]);

        $this->actingAs($this->cashier)
            ->get(route('reports.finance-income', [
                'date_from' => '2026-05-26',
                'date_to' => '2026-05-26',
                'income_category_id' => $serviceCategory->id,
                'patient_visit_id' => $visit->id,
            ]))
            ->assertOk()
            ->assertSee('Ma Hla')
            ->assertSee('4,000.00')
            ->assertDontSee('900.00')
            ->assertDontSee('Diagnosis')
            ->assertDontSee('Appointment');
    }

    public function test_expense_report_filters_by_date_and_category(): void
    {
        $rent = ExpenseCategory::where('name', 'Rent')->firstOrFail();
        $utilities = ExpenseCategory::where('name', 'Utilities')->firstOrFail();

        ExpenseEntry::create([
            'expense_category_id' => $rent->id,
            'amount' => 120000,
            'expense_date' => '2026-05-26',
            'payment_method' => ExpenseEntry::PAYMENT_CASH,
            'payee' => 'Landlord',
            'created_by' => $this->cashier->id,
        ]);

        ExpenseEntry::create([
            'expense_category_id' => $utilities->id,
            'amount' => 15000,
            'expense_date' => '2026-05-10',
            'payment_method' => ExpenseEntry::PAYMENT_CASH,
            'created_by' => $this->cashier->id,
        ]);

        $this->actingAs($this->cashier)
            ->get(route('reports.finance-expenses', [
                'date_from' => '2026-05-26',
                'date_to' => '2026-05-26',
                'expense_category_id' => $rent->id,
            ]))
            ->assertOk()
            ->assertSee('120,000.00')
            ->assertDontSee('15,000.00');
    }

    public function test_guest_and_stock_manager_cannot_access_finance_reports(): void
    {
        $this->get(route('reports.finance-summary'))->assertRedirect(route('login'));
        $this->get(route('reports.finance-income'))->assertRedirect(route('login'));

        $stockManager = User::factory()->create(['role' => User::ROLE_STOCK_MANAGER]);

        $this->actingAs($stockManager)
            ->get(route('reports.finance-summary'))
            ->assertForbidden();

        $this->actingAs($stockManager)
            ->get(route('reports.finance-income'))
            ->assertForbidden();
    }

    private function seedFinanceDataForSummary(): void
    {
        $serviceCategory = IncomeCategory::where('name', 'Consultation Fee')->firstOrFail();
        $generalCategory = IncomeCategory::where('name', 'Other Income')->firstOrFail();
        $rent = ExpenseCategory::where('name', 'Rent')->firstOrFail();

        IncomeEntry::create([
            'income_category_id' => $serviceCategory->id,
            'amount' => 3000,
            'payment_method' => IncomeEntry::PAYMENT_CASH,
            'received_at' => '2026-05-26 10:00:00',
            'received_by' => $this->cashier->id,
        ]);

        IncomeEntry::create([
            'income_category_id' => $generalCategory->id,
            'amount' => 1000,
            'payment_method' => IncomeEntry::PAYMENT_CASH,
            'received_at' => '2026-05-26 11:00:00',
            'received_by' => $this->cashier->id,
        ]);

        ExpenseEntry::create([
            'expense_category_id' => $rent->id,
            'amount' => 2000,
            'expense_date' => '2026-05-26',
            'payment_method' => ExpenseEntry::PAYMENT_CASH,
            'created_by' => $this->cashier->id,
        ]);

        Sale::create([
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
            'sold_at' => '2026-05-26 14:00:00',
        ]);
    }
}
