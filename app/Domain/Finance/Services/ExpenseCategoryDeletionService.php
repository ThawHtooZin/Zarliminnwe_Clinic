<?php

namespace App\Domain\Finance\Services;

use App\Domain\Shared\Exceptions\DeletionBlockException;
use App\Models\ExpenseCategory;

class ExpenseCategoryDeletionService
{
    /**
     * @throws DeletionBlockException
     */
    public function delete(ExpenseCategory $category): void
    {
        $entryCount = $category->expenseEntries()->count();

        if ($entryCount > 0) {
            throw new DeletionBlockException([
                'expense entry(ies)' => $entryCount,
            ]);
        }

        $category->delete();
    }
}
