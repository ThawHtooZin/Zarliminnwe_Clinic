<?php

namespace App\Http\Controllers\Finance;

use App\Domain\Audit\Services\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExpenseCategoryController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(): View
    {
        $categories = ExpenseCategory::query()
            ->latest()
            ->paginate(15);

        return view('finance.expense-categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('finance.expense-categories.form', [
            'category' => new ExpenseCategory(['is_active' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $category = ExpenseCategory::create($this->validated($request));
        $this->auditLogger->log('expense_category.created', $category, null, $category->toArray());

        return redirect()->route('finance.expense-categories.index')->with('status', 'Expense category created.');
    }

    public function edit(ExpenseCategory $expenseCategory): View
    {
        return view('finance.expense-categories.form', ['category' => $expenseCategory]);
    }

    public function update(Request $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        $oldValues = $expenseCategory->toArray();
        $expenseCategory->update($this->validated($request, $expenseCategory));
        $this->auditLogger->log('expense_category.updated', $expenseCategory, $oldValues, $expenseCategory->fresh()->toArray());

        return redirect()->route('finance.expense-categories.index')->with('status', 'Expense category updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?ExpenseCategory $category = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('expense_categories', 'name')->ignore($category),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
