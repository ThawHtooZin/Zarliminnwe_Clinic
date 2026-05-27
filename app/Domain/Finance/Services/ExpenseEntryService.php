<?php

namespace App\Domain\Finance\Services;

use App\Domain\Audit\Services\AuditLogger;
use App\Models\ExpenseCategory;
use App\Models\ExpenseEntry;
use App\Models\User;
use InvalidArgumentException;

class ExpenseEntryService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): ExpenseEntry
    {
        $this->validateCategoryIsActive((int) $data['expense_category_id']);
        $this->validatePositiveAmount($data['amount']);

        $entry = ExpenseEntry::create($data + [
            'created_by' => $user->id,
        ]);

        $this->auditLogger->log('expense_entry.created', $entry, null, $entry->toArray());

        return $entry;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ExpenseEntry $entry, array $data): ExpenseEntry
    {
        $this->validateCategoryIsActive((int) $data['expense_category_id']);
        $this->validatePositiveAmount($data['amount']);

        $oldValues = $entry->toArray();
        $entry->update($data);
        $entry = $entry->fresh();

        $this->auditLogger->log('expense_entry.updated', $entry, $oldValues, $entry->toArray());

        return $entry;
    }

    private function validateCategoryIsActive(int $categoryId): void
    {
        $category = ExpenseCategory::query()->find($categoryId);

        if (! $category) {
            throw new InvalidArgumentException('Expense category not found.');
        }

        if (! $category->is_active) {
            throw new InvalidArgumentException('Inactive expense categories cannot be used for expense entries.');
        }
    }

    private function validatePositiveAmount(mixed $amount): void
    {
        if ((float) $amount <= 0) {
            throw new InvalidArgumentException('Expense amount must be greater than zero.');
        }
    }
}
