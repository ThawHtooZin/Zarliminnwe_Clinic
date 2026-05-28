<?php

namespace App\Http\Controllers\Reports;

use App\Domain\Finance\Services\FinanceReportService;
use App\Domain\Finance\Services\FinanceSummaryService;
use App\Domain\Finance\Services\UnifiedIncomeQueryService;
use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use App\Models\ExpenseEntry;
use App\Models\IncomeCategory;
use App\Models\IncomeEntry;
use App\Models\PatientVisitRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FinanceReportController extends Controller
{
    public function __construct(
        private readonly FinanceSummaryService $financeSummaryService,
        private readonly FinanceReportService $financeReportService
    ) {}

    public function financeSummary(Request $request): View
    {
        $filters = $this->summaryFilters($request);
        $summary = $this->financeSummaryService->summarize($filters);

        return view('reports.finance-summary', [
            'summary' => $summary,
            'filters' => $filters,
            'incomeCategories' => IncomeCategory::query()->orderBy('name')->get(),
            'expenseCategories' => ExpenseCategory::query()->orderBy('name')->get(),
        ]);
    }

    public function incomeReport(Request $request): View
    {
        $filters = $this->incomeReportFilters($request);

        return view('reports.finance-income', [
            'unifiedIncomeLines' => $this->financeReportService->incomeReport($filters),
            'filters' => $filters,
            'categories' => IncomeCategory::query()->orderBy('name')->get(),
            'patientVisits' => PatientVisitRecord::query()->with('patient')->latest('visited_at')->limit(100)->get(),
            'users' => User::query()->orderBy('name')->get(),
        ]);
    }

    public function expenseReport(Request $request): View
    {
        $filters = $this->expenseReportFilters($request);

        return view('reports.finance-expenses', [
            'expenseEntries' => $this->financeReportService->expenseReport($filters),
            'filters' => $filters,
            'categories' => ExpenseCategory::query()->orderBy('name')->get(),
            'users' => User::query()->orderBy('name')->get(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function summaryFilters(Request $request): array
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'income_category_id' => ['nullable', 'integer', 'exists:income_categories,id'],
            'expense_category_id' => ['nullable', 'integer', 'exists:expense_categories,id'],
            'payment_method' => ['nullable', Rule::in(IncomeEntry::paymentMethods())],
        ]);

        return $this->withDefaultDateRange($filters);
    }

    /**
     * @return array<string, mixed>
     */
    private function incomeReportFilters(Request $request): array
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'income_category_id' => ['nullable', $this->incomeCategoryFilterRule()],
            'payment_method' => ['nullable', Rule::in(IncomeEntry::paymentMethods())],
            'patient_visit_id' => ['nullable', 'integer', 'exists:patient_visit_records,id'],
            'received_by' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        return $this->withDefaultDateRange($filters);
    }

    /**
     * @return array<string, mixed>
     */
    private function expenseReportFilters(Request $request): array
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'expense_category_id' => ['nullable', 'integer', 'exists:expense_categories,id'],
            'payment_method' => ['nullable', Rule::in(ExpenseEntry::paymentMethods())],
            'payee' => ['nullable', 'string', 'max:255'],
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        return $this->withDefaultDateRange($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function withDefaultDateRange(array $filters): array
    {
        $filters['date_from'] = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $filters['date_to'] = $filters['date_to'] ?? now()->toDateString();

        return $filters;
    }

    /**
     * @return \Closure(string, mixed, \Closure): void
     */
    private function incomeCategoryFilterRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            if ($value === UnifiedIncomeQueryService::PHARMACY_SALE_FILTER) {
                return;
            }

            if (! is_numeric($value) || ! IncomeCategory::query()->whereKey((int) $value)->exists()) {
                $fail('The selected category is invalid.');
            }
        };
    }
}
