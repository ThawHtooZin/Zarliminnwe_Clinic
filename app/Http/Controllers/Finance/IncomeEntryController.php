<?php

namespace App\Http\Controllers\Finance;

use App\Domain\Finance\Services\IncomeEntryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\IncomeEntryRequest;
use App\Models\IncomeCategory;
use App\Models\IncomeEntry;
use App\Models\PatientVisit;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class IncomeEntryController extends Controller
{
    public function __construct(private readonly IncomeEntryService $incomeEntryService) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'received_from' => ['nullable', 'date'],
            'received_to' => ['nullable', 'date'],
            'income_category_id' => ['nullable', 'integer', 'exists:income_categories,id'],
            'payment_method' => ['nullable', Rule::in(IncomeEntry::paymentMethods())],
            'patient_visit_id' => ['nullable', 'integer', 'exists:patient_visits,id'],
            'received_by' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $incomeEntries = IncomeEntry::query()
            ->with(['incomeCategory', 'patientVisit', 'receivedBy'])
            ->when($filters['received_from'] ?? null, fn ($query, string $from) => $query->whereDate('received_at', '>=', $from))
            ->when($filters['received_to'] ?? null, fn ($query, string $to) => $query->whereDate('received_at', '<=', $to))
            ->when($filters['income_category_id'] ?? null, fn ($query, int $categoryId) => $query->where('income_category_id', $categoryId))
            ->when($filters['payment_method'] ?? null, fn ($query, string $method) => $query->where('payment_method', $method))
            ->when($filters['patient_visit_id'] ?? null, fn ($query, int $visitId) => $query->where('patient_visit_id', $visitId))
            ->when($filters['received_by'] ?? null, fn ($query, int $userId) => $query->where('received_by', $userId))
            ->latest('received_at')
            ->paginate(15)
            ->withQueryString();

        return view('finance.income.index', [
            'incomeEntries' => $incomeEntries,
            'filters' => $filters,
            'categories' => IncomeCategory::query()->orderBy('name')->get(),
            'patientVisits' => PatientVisit::query()->latest('visited_at')->limit(100)->get(),
            'users' => User::query()->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $prefilledVisitId = $request->integer('patient_visit_id') ?: null;

        return view('finance.income.form', [
            'incomeEntry' => new IncomeEntry([
                'patient_visit_id' => $prefilledVisitId,
                'received_at' => now(),
                'payment_method' => IncomeEntry::PAYMENT_CASH,
            ]),
            'categories' => IncomeCategory::active()->orderBy('name')->get(),
            'patientVisits' => PatientVisit::query()->latest('visited_at')->get(),
        ]);
    }

    public function store(IncomeEntryRequest $request): RedirectResponse
    {
        try {
            $entry = $this->incomeEntryService->create(
                $request->incomeEntryData(),
                $request->user()
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['form' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('finance.income.index')->with('status', 'Income entry recorded.');
    }

    public function edit(IncomeEntry $incomeEntry): View
    {
        return view('finance.income.form', [
            'incomeEntry' => $incomeEntry,
            'categories' => IncomeCategory::active()->orderBy('name')->get(),
            'patientVisits' => PatientVisit::query()->latest('visited_at')->get(),
        ]);
    }

    public function update(IncomeEntryRequest $request, IncomeEntry $incomeEntry): RedirectResponse
    {
        try {
            $this->incomeEntryService->update($incomeEntry, $request->incomeEntryData());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['form' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('finance.income.index')->with('status', 'Income entry updated.');
    }
}
