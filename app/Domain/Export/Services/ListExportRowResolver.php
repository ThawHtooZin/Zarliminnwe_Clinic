<?php

namespace App\Domain\Export\Services;

use App\Domain\Finance\Data\UnifiedIncomeLine;
use App\Domain\Finance\Services\UnifiedIncomeQueryService;
use App\Models\ExpenseCategory;
use App\Models\ExpenseEntry;
use App\Models\IncomeCategory;
use App\Models\IncomeEntry;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Sale;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ListExportRowResolver
{
    public function __construct(
        private readonly UnifiedIncomeQueryService $unifiedIncomeQueryService,
    ) {}

    /**
     * @return list<list<string>>
     */
    public function rows(string $exportKey, Request $request): array
    {
        return match ($exportKey) {
            'products' => $this->productRows($request),
            'product-categories' => $this->productCategoryRows(),
            'suppliers' => $this->supplierRows(),
            'finance.income-categories' => $this->incomeCategoryRows(),
            'finance.expense-categories' => $this->expenseCategoryRows(),
            'finance.income' => $this->incomeRows($request),
            'finance.expenses' => $this->expenseRows($request),
            default => [],
        };
    }

    /**
     * @return list<list<string>>
     */
    private function productRows(Request $request): array
    {
        $search = $request->string('search')->toString();

        $products = Product::query()
            ->with(['category', 'units'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('generic_name', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->get();

        return $products->map(function (Product $product): array {
            $generic = $product->generic_name ?: 'No generic name';

            return [
                $product->name."\n".$generic,
                (string) $product->sku,
                (string) ($product->category?->name ?? ''),
                $product->units->pluck('abbreviation')->implode(', ') ?: '-',
                $product->is_active ? 'Active' : 'Inactive',
            ];
        })->all();
    }

    /**
     * @return list<list<string>>
     */
    private function productCategoryRows(): array
    {
        return ProductCategory::query()
            ->withCount('products')
            ->latest()
            ->get()
            ->map(fn (ProductCategory $category): array => [
                $category->name,
                (string) $category->products_count,
                $category->is_active ? 'Active' : 'Inactive',
            ])
            ->all();
    }

    /**
     * @return list<list<string>>
     */
    private function supplierRows(): array
    {
        return Supplier::query()
            ->latest()
            ->get()
            ->map(fn (Supplier $supplier): array => [
                $supplier->name,
                $supplier->phone ?: '—',
                $supplier->email ?: '—',
                $supplier->is_active ? 'Active' : 'Inactive',
            ])
            ->all();
    }

    /**
     * @return list<list<string>>
     */
    private function incomeCategoryRows(): array
    {
        return IncomeCategory::query()
            ->latest()
            ->get()
            ->map(fn (IncomeCategory $category): array => [
                $category->name,
                ucfirst($category->type),
                $category->is_active ? 'Active' : 'Inactive',
            ])
            ->all();
    }

    /**
     * @return list<list<string>>
     */
    private function expenseCategoryRows(): array
    {
        return ExpenseCategory::query()
            ->latest()
            ->get()
            ->map(fn (ExpenseCategory $category): array => [
                $category->name,
                $category->is_active ? 'Active' : 'Inactive',
            ])
            ->all();
    }

    /**
     * @return list<list<string>>
     */
    private function incomeRows(Request $request): array
    {
        $paymentMethods = array_merge(IncomeEntry::paymentMethods(), [Sale::PAYMENT_MIXED]);

        $filters = $request->validate([
            'received_from' => ['nullable', 'date'],
            'received_to' => ['nullable', 'date'],
            'income_category_id' => ['nullable'],
            'payment_method' => ['nullable', Rule::in($paymentMethods)],
            'patient_visit_id' => ['nullable', 'integer', 'exists:patient_visit_records,id'],
            'received_by' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        return $this->unifiedIncomeQueryService
            ->collectionForFilters($filters)
            ->map(fn (UnifiedIncomeLine $line): array => $this->mapIncomeLine($line))
            ->all();
    }

    /**
     * @return list<list<string>>
     */
    private function expenseRows(Request $request): array
    {
        $filters = $request->validate([
            'expense_from' => ['nullable', 'date'],
            'expense_to' => ['nullable', 'date'],
            'expense_category_id' => ['nullable', 'integer', 'exists:expense_categories,id'],
            'payment_method' => ['nullable', Rule::in(ExpenseEntry::paymentMethods())],
            'payee' => ['nullable', 'string', 'max:255'],
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        return ExpenseEntry::query()
            ->with(['expenseCategory', 'createdBy'])
            ->when($filters['expense_from'] ?? null, fn ($query, string $from) => $query->whereDate('expense_date', '>=', $from))
            ->when($filters['expense_to'] ?? null, fn ($query, string $to) => $query->whereDate('expense_date', '<=', $to))
            ->when($filters['expense_category_id'] ?? null, fn ($query, int $categoryId) => $query->where('expense_category_id', $categoryId))
            ->when($filters['payment_method'] ?? null, fn ($query, string $method) => $query->where('payment_method', $method))
            ->when($filters['payee'] ?? null, fn ($query, string $payee) => $query->where('payee', 'like', '%'.$payee.'%'))
            ->when($filters['created_by'] ?? null, fn ($query, int $userId) => $query->where('created_by', $userId))
            ->latest('expense_date')
            ->latest('id')
            ->get()
            ->map(fn (ExpenseEntry $entry): array => [
                $entry->expense_date->format('M d, Y'),
                $entry->expenseCategory->name,
                number_format((float) $entry->amount, 2, '.', ''),
                $entry->payee ?: '—',
                ucwords(str_replace('_', ' ', $entry->payment_method)),
                $entry->createdBy?->name ?: '—',
            ])
            ->all();
    }

    /**
     * @return list<string>
     */
    private function mapIncomeLine(UnifiedIncomeLine $line): array
    {
        $category = $line->categoryLabel;

        if ($line->categoryType && ! $line->isPharmacySale()) {
            $category .= "\n".ucfirst($line->categoryType);
        }

        if ($line->description) {
            $category .= "\n".$line->description;
        }

        return [
            $line->occurredAt->format('M d, Y H:i'),
            $line->isPharmacySale() ? 'Pharmacy Sale' : 'Service Income',
            $category,
            $this->formatPatientVisit($line),
            number_format($line->amount, 2, '.', ''),
            ucwords(str_replace('_', ' ', $line->paymentMethod)),
            $line->recordedByName ?: '—',
        ];
    }

    private function formatPatientVisit(UnifiedIncomeLine $line): string
    {
        $visit = $line->patientVisitRecord;

        if ($visit === null) {
            return '—';
        }

        $visit->loadMissing('patient');

        $code = $visit->patient?->patient_code ?? '';
        $name = $visit->patient_name ?? $visit->patient?->name ?? '';

        return trim("{$code} — {$name}")."\n".$visit->visited_at->format('M d, Y H:i');
    }
}
