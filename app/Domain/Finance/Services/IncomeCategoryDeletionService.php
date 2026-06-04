<?php

namespace App\Domain\Finance\Services;

use App\Domain\Shared\Exceptions\DeletionBlockException;
use App\Models\IncomeCategory;

class IncomeCategoryDeletionService
{
    /**
     * @throws DeletionBlockException
     */
    public function delete(IncomeCategory $category): void
    {
        $entryCount = $category->incomeEntries()->count();

        if ($entryCount > 0) {
            throw new DeletionBlockException([
                'income entry(ies)' => $entryCount,
            ]);
        }

        $category->delete();
    }
}
