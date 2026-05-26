<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Audit\Services\AuditLogger;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\StockBatch;
use App\Models\StockLedger;
use InvalidArgumentException;

class StockAdjustmentService
{
    public function __construct(
        private readonly StockPostingService $stockPostingService,
        private readonly AuditLogger $auditLogger
    ) {}

    public function postManualAdjustment(
        Product $product,
        ProductUnit $unit,
        float|int|string $quantity,
        string $direction,
        string $reason,
        ?StockBatch $stockBatch = null
    ): StockLedger {
        $this->validate($product, $unit, $quantity, $direction, $reason, $stockBatch);

        $ledger = $this->stockPostingService->postMovement(
            product: $product,
            unit: $unit,
            quantity: $quantity,
            type: StockLedger::TYPE_ADJUSTMENT,
            direction: $direction,
            unitCost: 0,
            reference: null,
            stockBatch: $stockBatch,
            reason: $reason
        );

        $this->auditLogger->log('stock_adjustment.posted', $ledger, null, $ledger->toArray());

        return $ledger;
    }

    private function validate(
        Product $product,
        ProductUnit $unit,
        float|int|string $quantity,
        string $direction,
        string $reason,
        ?StockBatch $stockBatch
    ): void {
        if ($unit->product_id !== $product->id) {
            throw new InvalidArgumentException('The selected unit does not belong to the product.');
        }

        if ((float) $quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than zero.');
        }

        if (! in_array($direction, [StockLedger::DIRECTION_IN, StockLedger::DIRECTION_OUT], true)) {
            throw new InvalidArgumentException('Direction must be in or out.');
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException('Adjustment reason is required.');
        }

        if (($product->track_batch || $product->track_expiry) && ! $stockBatch) {
            throw new InvalidArgumentException('Batch is required for this product.');
        }

        if ($stockBatch && $stockBatch->product_id !== $product->id) {
            throw new InvalidArgumentException('The selected batch does not belong to the product.');
        }
    }
}
