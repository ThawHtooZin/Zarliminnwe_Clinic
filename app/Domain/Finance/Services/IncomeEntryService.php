<?php

namespace App\Domain\Finance\Services;

use App\Domain\Audit\Services\AuditLogger;
use App\Models\IncomeCategory;
use App\Models\IncomeEntry;
use App\Models\PatientVisitRecord;
use App\Models\User;
use InvalidArgumentException;

class IncomeEntryService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): IncomeEntry
    {
        $data = $this->normalizePatientVisitRecordId($data);
        $this->validateCategoryIsActive((int) $data['income_category_id']);
        $this->validateOptionalPatientVisitRecord($data['patient_visit_record_id'] ?? null);

        $entry = IncomeEntry::create($data + [
            'received_by' => $user->id,
        ]);

        $this->auditLogger->log('income_entry.created', $entry, null, $entry->toArray());

        return $entry;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(IncomeEntry $entry, array $data): IncomeEntry
    {
        $data = $this->normalizePatientVisitRecordId($data);
        $this->validateCategoryIsActive((int) $data['income_category_id']);
        $this->validateOptionalPatientVisitRecord($data['patient_visit_record_id'] ?? null);

        $oldValues = $entry->toArray();
        $entry->update($data);
        $entry = $entry->fresh();

        $this->auditLogger->log('income_entry.updated', $entry, $oldValues, $entry->toArray());

        return $entry;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizePatientVisitRecordId(array $data): array
    {
        if (array_key_exists('patient_visit_id', $data) && ! array_key_exists('patient_visit_record_id', $data)) {
            $data['patient_visit_record_id'] = $data['patient_visit_id'];
        }

        unset($data['patient_visit_id']);

        if (blank($data['patient_visit_record_id'] ?? null)) {
            $data['patient_visit_record_id'] = null;
        }

        return $data;
    }

    private function validateCategoryIsActive(int $categoryId): void
    {
        $category = IncomeCategory::query()->find($categoryId);

        if (! $category) {
            throw new InvalidArgumentException('Income category not found.');
        }

        if (! $category->is_active) {
            throw new InvalidArgumentException('Inactive income categories cannot be used for income entries.');
        }
    }

    private function validateOptionalPatientVisitRecord(mixed $patientVisitRecordId): void
    {
        if ($patientVisitRecordId === null) {
            return;
        }

        if (! PatientVisitRecord::query()->whereKey($patientVisitRecordId)->exists()) {
            throw new InvalidArgumentException('Patient visit record not found.');
        }
    }
}
