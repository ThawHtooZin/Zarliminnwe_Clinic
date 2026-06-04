<?php

namespace App\Http\Controllers\Finance;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Finance\Services\IncomeCategoryDeletionService;
use App\Domain\Shared\Exceptions\DeletionBlockException;
use App\Http\Controllers\Controller;
use App\Models\IncomeCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IncomeCategoryController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly IncomeCategoryDeletionService $deletionService,
    ) {}

    public function index(): View
    {
        $categories = IncomeCategory::query()
            ->latest()
            ->paginate(15);

        return view('finance.income-categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('finance.income-categories.form', [
            'category' => new IncomeCategory([
                'type' => IncomeCategory::TYPE_SERVICE,
                'is_active' => true,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $category = IncomeCategory::create($this->validated($request));
        $this->auditLogger->log('income_category.created', $category, null, $category->toArray());

        return redirect()->route('finance.income-categories.index')->with('status', 'Income category created.');
    }

    public function edit(IncomeCategory $incomeCategory): View
    {
        return view('finance.income-categories.form', ['category' => $incomeCategory]);
    }

    public function update(Request $request, IncomeCategory $incomeCategory): RedirectResponse
    {
        $oldValues = $incomeCategory->toArray();
        $incomeCategory->update($this->validated($request, $incomeCategory));
        $this->auditLogger->log('income_category.updated', $incomeCategory, $oldValues, $incomeCategory->fresh()->toArray());

        return redirect()->route('finance.income-categories.index')->with('status', 'Income category updated.');
    }

    public function destroy(IncomeCategory $incomeCategory): RedirectResponse
    {
        try {
            $oldValues = $incomeCategory->toArray();
            $this->deletionService->delete($incomeCategory);
            $this->auditLogger->log('income_category.deleted', $incomeCategory, $oldValues, null);
        } catch (DeletionBlockException $exception) {
            return redirect()->route('finance.income-categories.index')->with('error', $exception->getMessage());
        }

        return redirect()->route('finance.income-categories.index')->with('status', 'Income category deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?IncomeCategory $category = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('income_categories', 'name')->ignore($category),
            ],
            'type' => ['required', Rule::in([IncomeCategory::TYPE_SERVICE, IncomeCategory::TYPE_GENERAL])],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
