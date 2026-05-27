<?php

namespace App\Domain\Finance\Services;

use App\Models\ExpenseEntry;
use App\Models\IncomeEntry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class FinanceReportService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function incomeReport(array $filters): LengthAwarePaginator
    {
        return $this->incomeQuery($filters)
            ->latest('received_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function expenseReport(array $filters): LengthAwarePaginator
    {
        return $this->expenseQuery($filters)
            ->latest('expense_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<IncomeEntry>
     */
    private function incomeQuery(array $filters): Builder
    {
        return IncomeEntry::query()
            ->with(['incomeCategory', 'patientVisit', 'receivedBy'])
            ->when($filters['date_from'] ?? null, fn ($query, string $from) => $query->whereDate('received_at', '>=', $from))
            ->when($filters['date_to'] ?? null, fn ($query, string $to) => $query->whereDate('received_at', '<=', $to))
            ->when($filters['income_category_id'] ?? null, fn ($query, int $categoryId) => $query->where('income_category_id', $categoryId))
            ->when($filters['payment_method'] ?? null, fn ($query, string $method) => $query->where('payment_method', $method))
            ->when($filters['patient_visit_id'] ?? null, fn ($query, int $visitId) => $query->where('patient_visit_id', $visitId))
            ->when($filters['received_by'] ?? null, fn ($query, int $userId) => $query->where('received_by', $userId));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<ExpenseEntry>
     */
    private function expenseQuery(array $filters): Builder
    {
        return ExpenseEntry::query()
            ->with(['expenseCategory', 'createdBy'])
            ->when($filters['date_from'] ?? null, fn ($query, string $from) => $query->whereDate('expense_date', '>=', $from))
            ->when($filters['date_to'] ?? null, fn ($query, string $to) => $query->whereDate('expense_date', '<=', $to))
            ->when($filters['expense_category_id'] ?? null, fn ($query, int $categoryId) => $query->where('expense_category_id', $categoryId))
            ->when($filters['payment_method'] ?? null, fn ($query, string $method) => $query->where('payment_method', $method))
            ->when($filters['payee'] ?? null, fn ($query, string $payee) => $query->where('payee', 'like', '%'.$payee.'%'))
            ->when($filters['created_by'] ?? null, fn ($query, int $userId) => $query->where('created_by', $userId));
    }
}
