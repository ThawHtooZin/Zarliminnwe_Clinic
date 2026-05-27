<?php

namespace App\Domain\Finance\Services;

use App\Models\ExpenseEntry;
use App\Models\IncomeCategory;
use App\Models\IncomeEntry;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinanceSummaryService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function summarize(array $filters): array
    {
        $serviceIncome = $this->sumIncomeByType(IncomeCategory::TYPE_SERVICE, $filters);
        $generalIncome = $this->sumIncomeByType(IncomeCategory::TYPE_GENERAL, $filters);
        $pharmacySalesIncome = $this->sumPharmacySales($filters);
        $expenseTotal = $this->sumExpenses($filters);

        $totalIncome = $serviceIncome + $generalIncome + $pharmacySalesIncome;
        $netBalance = $totalIncome - $expenseTotal;

        return [
            'service_income' => $serviceIncome,
            'general_income' => $generalIncome,
            'pharmacy_sales_income' => $pharmacySalesIncome,
            'total_income' => $totalIncome,
            'expense_total' => $expenseTotal,
            'net_balance' => $netBalance,
            'income_by_category' => $this->incomeByCategory($filters),
            'expense_by_category' => $this->expenseByCategory($filters),
            'income_by_payment_method' => $this->incomeByPaymentMethod($filters),
            'expense_by_payment_method' => $this->expenseByPaymentMethod($filters),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function sumIncomeByType(string $type, array $filters): float
    {
        if ($this->incomeCategoryFilterBlocksType($filters, $type)) {
            return 0.0;
        }

        $query = IncomeEntry::query()
            ->join('income_categories', 'income_entries.income_category_id', '=', 'income_categories.id')
            ->where('income_categories.type', $type);

        $this->applyIncomeFilters($query, $filters);

        return (float) $query->sum('income_entries.amount');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function sumPharmacySales(array $filters): float
    {
        $query = Sale::query()
            ->where('status', Sale::STATUS_COMPLETED);

        if (! empty($filters['date_from'])) {
            $query->whereDate('sold_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('sold_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        return (float) $query->sum('grand_total');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function sumExpenses(array $filters): float
    {
        $query = ExpenseEntry::query();

        $this->applyExpenseFilters($query, $filters);

        return (float) $query->sum('amount');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, object>
     */
    private function incomeByCategory(array $filters): Collection
    {
        $query = IncomeEntry::query()
            ->join('income_categories', 'income_entries.income_category_id', '=', 'income_categories.id')
            ->select(
                'income_categories.name',
                'income_categories.type',
                DB::raw('SUM(income_entries.amount) as total_amount')
            )
            ->groupBy('income_categories.id', 'income_categories.name', 'income_categories.type')
            ->orderBy('income_categories.name');

        $this->applyIncomeFilters($query, $filters);

        return $query->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, object>
     */
    private function expenseByCategory(array $filters): Collection
    {
        $query = ExpenseEntry::query()
            ->join('expense_categories', 'expense_entries.expense_category_id', '=', 'expense_categories.id')
            ->select(
                'expense_categories.name',
                DB::raw('SUM(expense_entries.amount) as total_amount')
            )
            ->groupBy('expense_categories.id', 'expense_categories.name')
            ->orderBy('expense_categories.name');

        $this->applyExpenseFilters($query, $filters);

        return $query->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, object>
     */
    private function incomeByPaymentMethod(array $filters): Collection
    {
        $query = IncomeEntry::query()
            ->select('payment_method', DB::raw('SUM(amount) as total_amount'))
            ->groupBy('payment_method')
            ->orderBy('payment_method');

        $this->applyIncomeFilters($query, $filters);

        return $query->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, object>
     */
    private function expenseByPaymentMethod(array $filters): Collection
    {
        $query = ExpenseEntry::query()
            ->select('payment_method', DB::raw('SUM(amount) as total_amount'))
            ->groupBy('payment_method')
            ->orderBy('payment_method');

        $this->applyExpenseFilters($query, $filters);

        return $query->get();
    }

    /**
     * @param  Builder<IncomeEntry>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyIncomeFilters($query, array $filters): void
    {
        if (! empty($filters['date_from'])) {
            $query->whereDate('income_entries.received_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('income_entries.received_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['income_category_id'])) {
            $query->where('income_entries.income_category_id', $filters['income_category_id']);
        }

        if (! empty($filters['payment_method'])) {
            $query->where('income_entries.payment_method', $filters['payment_method']);
        }
    }

    /**
     * @param  Builder<ExpenseEntry>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyExpenseFilters($query, array $filters): void
    {
        if (! empty($filters['date_from'])) {
            $query->whereDate('expense_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('expense_date', '<=', $filters['date_to']);
        }

        if (! empty($filters['expense_category_id'])) {
            $query->where('expense_category_id', $filters['expense_category_id']);
        }

        if (! empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function incomeCategoryFilterBlocksType(array $filters, string $type): bool
    {
        if (empty($filters['income_category_id'])) {
            return false;
        }

        $category = IncomeCategory::query()->find($filters['income_category_id']);

        return ! $category || $category->type !== $type;
    }
}
