<?php

namespace App\Domain\Patients\Services;

use App\Domain\Audit\Services\AuditLogger;
use App\Models\Patient;
use App\Models\User;

class PatientService
{
    public function __construct(
        private readonly PatientCodeGenerator $patientCodeGenerator,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array{name: string, age: int|string, address: string}  $data
     */
    public function create(array $data, ?User $user = null): Patient
    {
        $patient = Patient::create([
            'patient_code' => $this->patientCodeGenerator->generate(),
            'name' => $data['name'],
            'age' => (int) $data['age'],
            'address' => $data['address'],
            'created_by' => $user?->id,
        ]);

        $this->auditLogger->log('patient.created', $patient, null, $patient->toArray());

        return $patient;
    }

    /**
     * @param  array{name: string, age: int|string, address: string}  $data
     */
    public function update(Patient $patient, array $data): Patient
    {
        $oldValues = $patient->toArray();

        $patient->update([
            'name' => $data['name'],
            'age' => (int) $data['age'],
            'address' => $data['address'],
        ]);

        $patient = $patient->fresh();
        $this->auditLogger->log('patient.updated', $patient, $oldValues, $patient->toArray());

        return $patient;
    }
}
