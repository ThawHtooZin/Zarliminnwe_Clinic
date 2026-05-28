<?php

namespace App\Http\Controllers\Finance;

use App\Domain\Finance\Services\ExpenseEntryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\ExpenseEntryRequest;
use App\Models\ExpenseCategory;
use App\Models\ExpenseEntry;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class ExpenseEntryController extends Controller
{
    public function __construct(private readonly ExpenseEntryService $expenseEntryService) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'expense_from' => ['nullable', 'date'],
            'expense_to' => ['nullable', 'date'],
            'expense_category_id' => ['nullable', 'integer', 'exists:expense_categories,id'],
            'payment_method' => ['nullable', Rule::in(ExpenseEntry::paymentMethods())],
            'payee' => ['nullable', 'string', 'max:255'],
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $expenseEntries = ExpenseEntry::query()
            ->with(['expenseCategory', 'createdBy'])
            ->when($filters['expense_from'] ?? null, fn ($query, string $from) => $query->whereDate('expense_date', '>=', $from))
            ->when($filters['expense_to'] ?? null, fn ($query, string $to) => $query->whereDate('expense_date', '<=', $to))
            ->when($filters['expense_category_id'] ?? null, fn ($query, int $categoryId) => $query->where('expense_category_id', $categoryId))
            ->when($filters['payment_method'] ?? null, fn ($query, string $method) => $query->where('payment_method', $method))
            ->when($filters['payee'] ?? null, fn ($query, string $payee) => $query->where('payee', 'like', '%'.$payee.'%'))
            ->when($filters['created_by'] ?? null, fn ($query, int $userId) => $query->where('created_by', $userId))
            ->latest('expense_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('finance.expenses.index', [
            'expenseEntries' => $expenseEntries,
            'filters' => $filters,
            'categories' => ExpenseCategory::query()->orderBy('name')->get(),
            'users' => User::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('finance.expenses.form', [
            'expenseEntry' => new ExpenseEntry([
                'expense_date' => now()->toDateString(),
                'payment_method' => ExpenseEntry::PAYMENT_CASH,
            ]),
            'categories' => ExpenseCategory::active()->orderBy('name')->get(),
        ]);
    }

    public function store(ExpenseEntryRequest $request): RedirectResponse
    {
        try {
            $this->expenseEntryService->create(
                $request->expenseEntryData(),
                $request->user()
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['form' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('finance.expenses.index')->with('status', 'Expense entry recorded.');
    }

    public function edit(ExpenseEntry $expenseEntry): View
    {
        return view('finance.expenses.form', [
            'expenseEntry' => $expenseEntry,
            'categories' => ExpenseCategory::active()->orderBy('name')->get(),
        ]);
    }

    public function update(ExpenseEntryRequest $request, ExpenseEntry $expenseEntry): RedirectResponse
    {
        try {
            $this->expenseEntryService->update($expenseEntry, $request->expenseEntryData());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['form' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('finance.expenses.index')->with('status', 'Expense entry updated.');
    }
}
