<?php

namespace App\Http\Controllers\Finance;

use App\Domain\Administration\Services\PermissionResolver;
use App\Domain\Finance\Services\IncomeEntryService;
use App\Domain\Finance\Services\UnifiedIncomeQueryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\IncomeEntryRequest;
use App\Models\IncomeCategory;
use App\Models\IncomeEntry;
use App\Models\PatientVisitRecord;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class IncomeEntryController extends Controller
{
    public function __construct(
        private readonly IncomeEntryService $incomeEntryService,
        private readonly UnifiedIncomeQueryService $unifiedIncomeQueryService,
        private readonly PermissionResolver $permissionResolver,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'received_from' => ['nullable', 'date'],
            'received_to' => ['nullable', 'date'],
            'income_category_id' => ['nullable', $this->incomeCategoryFilterRule()],
            'payment_method' => ['nullable', Rule::in(IncomeEntry::paymentMethods())],
            'patient_visit_id' => ['nullable', 'integer', 'exists:patient_visit_records,id'],
            'received_by' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $unifiedIncomeLines = $this->unifiedIncomeQueryService->paginatedForFilters($filters, 15);

        return view('finance.income.index', [
            'unifiedIncomeLines' => $unifiedIncomeLines,
            'filters' => $filters,
            'categories' => IncomeCategory::query()->orderBy('name')->get(),
            'patientVisits' => PatientVisitRecord::query()->with('patient')->latest('visited_at')->limit(100)->get(),
            'users' => User::query()->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $prefilledVisitId = $request->integer('patient_visit_id') ?: null;

        return view('finance.income.form', [
            'incomeEntry' => new IncomeEntry([
                'patient_visit_record_id' => $prefilledVisitId,
                'received_at' => now(),
                'payment_method' => IncomeEntry::PAYMENT_CASH,
            ]),
            'categories' => IncomeCategory::active()->orderBy('name')->get(),
            'patientVisits' => PatientVisitRecord::query()->with('patient')->latest('visited_at')->get(),
        ]);
    }

    public function store(IncomeEntryRequest $request): RedirectResponse
    {
        $patientVisitRecordId = (int) ($request->incomeEntryData()['patient_visit_record_id'] ?? 0);

        try {
            $this->incomeEntryService->create(
                $request->incomeEntryData(),
                $request->user()
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['form' => $exception->getMessage()])->withInput();
        }

        return $this->redirectAfterIncomeSave($request->user(), $patientVisitRecordId)
            ->with('status', 'Income entry recorded.');
    }

    public function edit(IncomeEntry $incomeEntry): View
    {
        return view('finance.income.form', [
            'incomeEntry' => $incomeEntry,
            'categories' => IncomeCategory::active()->orderBy('name')->get(),
            'patientVisits' => PatientVisitRecord::query()->with('patient')->latest('visited_at')->get(),
        ]);
    }

    public function update(IncomeEntryRequest $request, IncomeEntry $incomeEntry): RedirectResponse
    {
        try {
            $this->incomeEntryService->update($incomeEntry, $request->incomeEntryData());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['form' => $exception->getMessage()])->withInput();
        }

        return $this->redirectAfterIncomeSave($request->user(), (int) ($incomeEntry->patient_visit_record_id ?? 0))
            ->with('status', 'Income entry updated.');
    }

    private function redirectAfterIncomeSave(User $user, int $patientVisitRecordId): RedirectResponse
    {
        if ($patientVisitRecordId > 0 && $this->permissionResolver->canAccessRoute($user, 'patient-visits.show')) {
            return redirect()->route('patient-visits.show', $patientVisitRecordId);
        }

        if ($this->permissionResolver->canAccessRoute($user, 'finance.income.index')) {
            return redirect()->route('finance.income.index');
        }

        if ($this->permissionResolver->canAccessRoute($user, 'sales.pos')) {
            return redirect()->route('sales.pos');
        }

        return redirect()->route('dashboard');
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
