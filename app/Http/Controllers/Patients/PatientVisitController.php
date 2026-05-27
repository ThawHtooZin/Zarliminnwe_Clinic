<?php

namespace App\Http\Controllers\Patients;

use App\Domain\Audit\Services\AuditLogger;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patients\PatientVisitRequest;
use App\Models\PatientVisit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientVisitController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'patient_name' => ['nullable', 'string', 'max:255'],
            'visited_from' => ['nullable', 'date'],
            'visited_to' => ['nullable', 'date'],
        ]);

        $patientVisits = PatientVisit::query()
            ->with('createdBy')
            ->when($filters['patient_name'] ?? null, function ($query, string $patientName): void {
                $query->where('patient_name', 'like', '%'.$patientName.'%');
            })
            ->when($filters['visited_from'] ?? null, function ($query, string $visitedFrom): void {
                $query->whereDate('visited_at', '>=', $visitedFrom);
            })
            ->when($filters['visited_to'] ?? null, function ($query, string $visitedTo): void {
                $query->whereDate('visited_at', '<=', $visitedTo);
            })
            ->latest('visited_at')
            ->paginate(15)
            ->withQueryString();

        return view('patient-visits.index', compact('patientVisits', 'filters'));
    }

    public function create(): View
    {
        return view('patient-visits.form', [
            'patientVisit' => new PatientVisit(['visited_at' => now()]),
        ]);
    }

    public function store(PatientVisitRequest $request): RedirectResponse
    {
        $patientVisit = PatientVisit::create($this->patientVisitData($request) + [
            'created_by' => $request->user()->id,
        ]);

        $this->auditLogger->log('patient_visit.created', $patientVisit, null, $patientVisit->toArray());

        return redirect()->route('patient-visits.show', $patientVisit)->with('status', 'Patient visit created.');
    }

    public function show(PatientVisit $patientVisit): View
    {
        $patientVisit->load([
            'createdBy',
            'incomeEntries.incomeCategory',
            'incomeEntries.receivedBy',
        ]);

        return view('patient-visits.show', compact('patientVisit'));
    }

    public function edit(PatientVisit $patientVisit): View
    {
        return view('patient-visits.form', compact('patientVisit'));
    }

    public function update(PatientVisitRequest $request, PatientVisit $patientVisit): RedirectResponse
    {
        $oldValues = $patientVisit->toArray();
        $patientVisit->update($this->patientVisitData($request));

        $this->auditLogger->log('patient_visit.updated', $patientVisit, $oldValues, $patientVisit->fresh()->toArray());

        return redirect()->route('patient-visits.show', $patientVisit)->with('status', 'Patient visit updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function patientVisitData(PatientVisitRequest $request): array
    {
        return $request->safe()->only([
            'patient_name',
            'age',
            'visited_at',
        ]);
    }
}
