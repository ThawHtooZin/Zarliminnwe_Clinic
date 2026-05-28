<?php

namespace App\Domain\Patients\Services;

use App\Domain\Audit\Services\AuditLogger;
use App\Models\Patient;
use App\Models\PatientDiagnosis;
use App\Models\PatientVisitRecord;
use App\Models\User;
use Illuminate\Support\Carbon;

class PatientVisitRecordService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array{visited_at: Carbon|string, status?: string}  $data
     */
    public function createForPatient(Patient $patient, array $data, ?User $user = null): PatientVisitRecord
    {
        $record = PatientVisitRecord::create([
            'patient_id' => $patient->id,
            'visited_at' => $data['visited_at'],
            'status' => $data['status'] ?? PatientVisitRecord::STATUS_OPEN,
            'created_by' => $user?->id,
        ]);

        $this->auditLogger->log('patient_visit_record.created', $record, null, $this->auditPayload($record));

        return $record->load('patient');
    }

    /**
     * @param  array{visited_at: Carbon|string}  $data
     */
    public function updateForPatient(PatientVisitRecord $record, array $data): PatientVisitRecord
    {
        $oldValues = $this->auditPayload($record->load('patient'));

        $record->update([
            'visited_at' => $data['visited_at'],
        ]);

        $record = $record->fresh(['patient']);

        $this->auditLogger->log('patient_visit_record.updated', $record, $oldValues, $this->auditPayload($record));

        return $record;
    }

    public function addDiagnosis(PatientVisitRecord $record, string $diagnosisText, User $user): PatientDiagnosis
    {
        $diagnosis = PatientDiagnosis::create([
            'patient_visit_record_id' => $record->id,
            'diagnosis_text' => $diagnosisText,
            'recorded_at' => now(),
            'recorded_by' => $user->id,
        ]);

        $this->auditLogger->log('patient_diagnosis.created', $diagnosis, null, $diagnosis->toArray());

        return $diagnosis;
    }

    public function updateDiagnosis(PatientDiagnosis $diagnosis, string $diagnosisText): PatientDiagnosis
    {
        $oldValues = $diagnosis->toArray();

        $diagnosis->update([
            'diagnosis_text' => $diagnosisText,
        ]);

        $diagnosis = $diagnosis->fresh();

        $this->auditLogger->log('patient_diagnosis.updated', $diagnosis, $oldValues, $diagnosis->toArray());

        return $diagnosis;
    }

    /**
     * @return array<string, mixed>
     */
    private function auditPayload(PatientVisitRecord $record): array
    {
        return [
            'patient_id' => $record->patient_id,
            'patient_code' => $record->patient->patient_code,
            'patient_name' => $record->patient->name,
            'age' => $record->patient->age,
            'visited_at' => $record->visited_at?->toIso8601String(),
            'status' => $record->status,
        ];
    }
}
