<?php

namespace App\Http\Controllers\Patients;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'patient_code' => ['nullable', 'string', 'max:50'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $patients = Patient::query()
            ->when($filters['patient_code'] ?? null, function ($query, string $code): void {
                $query->where('patient_code', 'like', '%'.$code.'%');
            })
            ->when($filters['name'] ?? null, function ($query, string $name): void {
                $query->where('name', 'like', '%'.$name.'%');
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('patients.index', compact('patients', 'filters'));
    }

    public function create(): View
    {
        return view('patients.form', [
            'patient' => new Patient(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'age' => ['required', 'integer', 'min:0', 'max:150'],
            'address' => ['required', 'string', 'max:255'],
        ]);

        /** @var \App\Domain\Patients\Services\PatientService $patientService */
        $patientService = app(\App\Domain\Patients\Services\PatientService::class);

        $patient = $patientService->create($data, $request->user());

        return redirect()->route('patients.show', $patient)->with('status', 'Patient created.');
    }

    public function show(Patient $patient): View
    {
        $patient->load([
            'visitRecords' => fn ($query) => $query->latest('visited_at'),
        ]);

        return view('patients.show', compact('patient'));
    }

    public function edit(Patient $patient): View
    {
        return view('patients.form', compact('patient'));
    }

    public function update(Request $request, Patient $patient): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'age' => ['required', 'integer', 'min:0', 'max:150'],
            'address' => ['required', 'string', 'max:255'],
        ]);

        /** @var \App\Domain\Patients\Services\PatientService $patientService */
        $patientService = app(\App\Domain\Patients\Services\PatientService::class);

        $patientService->update($patient, $data);

        return redirect()->route('patients.show', $patient)->with('status', 'Patient updated.');
    }
}

