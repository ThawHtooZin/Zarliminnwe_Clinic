<?php

namespace App\Domain\Sales\Services;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Inventory\Services\StockPostingService;
use App\Models\Sale;
use App\Models\StockLedger;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SaleVoidService
{
    public function __construct(
        private readonly StockPostingService $stockPostingService,
        private readonly AuditLogger $auditLogger
    ) {}

    public function void(Sale $sale, User $user, string $reason): Sale
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new InvalidArgumentException('Void reason is required.');
        }

        return DB::transaction(function () use ($sale, $user, $reason): Sale {
            $sale = Sale::query()
                ->whereKey($sale->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $sale->isCompleted()) {
                throw new InvalidArgumentException('Only completed sales can be voided.');
            }

            $originalLedgers = StockLedger::query()
                ->with(['product', 'productUnit', 'stockBatch'])
                ->where('reference_type', Sale::class)
                ->where('reference_id', $sale->id)
                ->where('type', StockLedger::TYPE_SALE)
                ->where('direction', StockLedger::DIRECTION_OUT)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($originalLedgers->isEmpty()) {
                throw new InvalidArgumentException('No sale stock movements were found to reverse.');
            }

            foreach ($originalLedgers as $ledger) {
                $this->stockPostingService->postMovement(
                    product: $ledger->product,
                    unit: $ledger->productUnit,
                    quantity: $ledger->quantity,
                    type: StockLedger::TYPE_SALE_VOID,
                    direction: StockLedger::DIRECTION_IN,
                    unitCost: $ledger->unit_cost,
                    reference: $sale,
                    stockBatch: $ledger->stockBatch,
                    reason: 'Void sale '.$sale->sale_number.': '.$reason
                );
            }

            $oldValues = $sale->toArray();

            $sale->update([
                'status' => Sale::STATUS_VOIDED,
                'voided_by' => $user->id,
                'voided_at' => now(),
                'void_reason' => $reason,
            ]);

            $sale = $sale->fresh()->load(['lines.product', 'lines.productUnit', 'voidedBy']);
            $this->auditLogger->log('sale.voided', $sale, $oldValues, $sale->toArray());

            return $sale;
        });
    }
}
