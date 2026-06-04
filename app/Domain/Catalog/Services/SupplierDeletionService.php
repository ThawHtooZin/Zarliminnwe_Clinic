<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Shared\Exceptions\DeletionBlockException;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class SupplierDeletionService
{
    /**
     * @throws DeletionBlockException
     */
    public function delete(Supplier $supplier): void
    {
        $receiptCount = DB::table('purchase_receipts')->where('supplier_id', $supplier->id)->count();

        if ($receiptCount > 0) {
            throw new DeletionBlockException([
                'purchase receipt(s)' => $receiptCount,
            ]);
        }

        $supplier->delete();
    }
}
