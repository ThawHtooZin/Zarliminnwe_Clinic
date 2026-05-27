<?php

namespace App\Domain\Finance\Services;

use App\Domain\Audit\Services\AuditLogger;
use App\Models\IncomeCategory;
use App\Models\IncomeEntry;
use App\Models\PatientVisit;
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
        $this->validateCategoryIsActive((int) $data['income_category_id']);
        $this->validateOptionalPatientVisit($data['patient_visit_id'] ?? null);

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
        $this->validateCategoryIsActive((int) $data['income_category_id']);
        $this->validateOptionalPatientVisit($data['patient_visit_id'] ?? null);

        $oldValues = $entry->toArray();
        $entry->update($data);
        $entry = $entry->fresh();

        $this->auditLogger->log('income_entry.updated', $entry, $oldValues, $entry->toArray());

        return $entry;
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

    private function validateOptionalPatientVisit(mixed $patientVisitId): void
    {
        if ($patientVisitId === null) {
            return;
        }

        if (! PatientVisit::query()->whereKey($patientVisitId)->exists()) {
            throw new InvalidArgumentException('Patient visit not found.');
        }
    }
}
