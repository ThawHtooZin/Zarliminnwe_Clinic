<?php

namespace App\Http\Controllers\Patients;

use App\Domain\Finance\Services\UnifiedIncomeQueryService;
use App\Domain\Patients\Services\PatientVisitRecordService;
use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientDiagnosis;
use App\Models\PatientVisitRecord;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientVisitController extends Controller
{
    public function __construct(
        private readonly PatientVisitRecordService $patientVisitRecordService,
        private readonly UnifiedIncomeQueryService $unifiedIncomeQueryService,
    ) {}

    public function createForPatient(Patient $patient): View
    {
        return view('patient-visits.form', [
            'patient' => $patient,
            'patientVisit' => new PatientVisitRecord([
                'patient_id' => $patient->id,
                'visited_at' => now(),
            ]),
        ]);
    }

    public function storeForPatient(Request $request, Patient $patient): RedirectResponse
    {
        $data = $request->validate([
            'visited_at' => ['required', 'date'],
        ]);

        $visitRecord = $this->patientVisitRecordService->createForPatient($patient, $data, $request->user());

        return redirect()->route('patient-visits.show', $visitRecord)->with('status', 'Patient visit created.');
    }

    public function show(PatientVisitRecord $patientVisit): View
    {
        $patientVisit->load([
            'patient',
            'createdBy',
            'diagnoses.recordedBy',
            'incomeEntries.incomeCategory',
            'incomeEntries.receivedBy',
        ]);

        return view('patient-visits.show', [
            'patientVisit' => $patientVisit,
            'visitIncomeLines' => $this->unifiedIncomeQueryService->forPatientVisit($patientVisit),
            'visitIncomeTotal' => $this->unifiedIncomeQueryService->visitIncomeTotal($patientVisit),
        ]);
    }

    public function editForPatient(Patient $patient, PatientVisitRecord $patientVisit): View
    {
        abort_unless($patientVisit->patient_id === $patient->id, 404);

        return view('patient-visits.form', [
            'patient' => $patient,
            'patientVisit' => $patientVisit,
        ]);
    }

    public function updateForPatient(Request $request, Patient $patient, PatientVisitRecord $patientVisit): RedirectResponse
    {
        abort_unless($patientVisit->patient_id === $patient->id, 404);

        $data = $request->validate([
            'visited_at' => ['required', 'date'],
        ]);

        $this->patientVisitRecordService->updateForPatient($patientVisit, $data);

        return redirect()->route('patient-visits.show', $patientVisit)->with('status', 'Patient visit updated.');
    }

    public function storeDiagnosis(Request $request, PatientVisitRecord $patientVisit): RedirectResponse
    {
        $data = $request->validate([
            'diagnosis_text' => ['required', 'string', 'max:1000'],
        ]);

        $this->patientVisitRecordService->addDiagnosis($patientVisit, $data['diagnosis_text'], $request->user());

        return redirect()->route('patient-visits.show', $patientVisit)->with('status', 'Diagnosis added.');
    }

    public function editDiagnosis(PatientVisitRecord $patientVisit, PatientDiagnosis $diagnosis): View
    {
        abort_unless($diagnosis->patient_visit_record_id === $patientVisit->id, 404);

        return view('patient-visits.diagnosis-edit', compact('patientVisit', 'diagnosis'));
    }

    public function updateDiagnosis(Request $request, PatientVisitRecord $patientVisit, PatientDiagnosis $diagnosis): RedirectResponse
    {
        abort_unless($diagnosis->patient_visit_record_id === $patientVisit->id, 404);

        $data = $request->validate([
            'diagnosis_text' => ['required', 'string', 'max:1000'],
        ]);

        $this->patientVisitRecordService->updateDiagnosis($diagnosis, $data['diagnosis_text']);

        return redirect()->route('patient-visits.show', $patientVisit)->with('status', 'Diagnosis updated.');
    }

    public function todayRecent(): JsonResponse
    {
        $visits = PatientVisitRecord::query()
            ->with('patient')
            ->whereDate('visited_at', now()->toDateString())
            ->whereDoesntHave('sales', function ($query): void {
                $query->where('status', Sale::STATUS_COMPLETED);
            })
            ->latest('visited_at')
            ->limit(20)
            ->get();

        return response()->json([
            'data' => $visits->map(fn (PatientVisitRecord $visit): array => [
                'id' => $visit->id,
                'patient_code' => $visit->patient?->patient_code,
                'patient_name' => $visit->patient_name,
                'visited_at' => $visit->visited_at?->toIso8601String(),
            ])->values(),
        ]);
    }
}
