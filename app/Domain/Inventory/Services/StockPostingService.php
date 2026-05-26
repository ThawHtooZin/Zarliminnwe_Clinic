<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Audit\Services\AuditLogger;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\PurchaseReceipt;
use App\Models\StockBalance;
use App\Models\StockBatch;
use App\Models\StockLedger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockPostingService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function postOpeningStock(
        Product $product,
        ProductUnit $unit,
        float|int|string $quantity,
        array $batchData = [],
        ?string $reason = null
    ): StockLedger {
        return DB::transaction(function () use ($product, $unit, $quantity, $batchData, $reason): StockLedger {
            $batch = $this->resolveBatch($product, $batchData);

            $ledger = $this->postMovement(
                product: $product,
                unit: $unit,
                quantity: $quantity,
                type: StockLedger::TYPE_OPENING_STOCK,
                direction: StockLedger::DIRECTION_IN,
                unitCost: 0,
                reference: null,
                stockBatch: $batch,
                reason: $reason ?: 'Opening stock'
            );

            $this->auditLogger->log('opening_stock.posted', $ledger, null, $ledger->toArray());

            return $ledger;
        });
    }

    public function postPurchaseReceipt(PurchaseReceipt $receipt): void
    {
        if ($receipt->isPosted()) {
            throw new InvalidArgumentException('Posted purchase receipts cannot be posted again.');
        }

        DB::transaction(function () use ($receipt): void {
            $receipt->load('lines.product', 'lines.productUnit');

            foreach ($receipt->lines as $line) {
                $batch = $this->resolveBatch($line->product, [
                    'batch_number' => $line->batch_number,
                    'expires_at' => $line->expires_at,
                    'received_at' => $receipt->received_at,
                ]);

                $this->postMovement(
                    product: $line->product,
                    unit: $line->productUnit,
                    quantity: $line->quantity,
                    type: StockLedger::TYPE_PURCHASE_RECEIPT,
                    direction: StockLedger::DIRECTION_IN,
                    unitCost: $line->unit_cost,
                    reference: $receipt,
                    stockBatch: $batch,
                    reason: 'Purchase receipt '.$receipt->receipt_number
                );
            }

            $receipt->update([
                'status' => PurchaseReceipt::STATUS_POSTED,
                'posted_at' => now(),
            ]);

            $this->auditLogger->log('purchase_receipt.posted', $receipt, null, $receipt->toArray());
        });
    }

    public function postMovement(
        Product $product,
        ProductUnit $unit,
        float|int|string $quantity,
        string $type,
        string $direction,
        float|int|string $unitCost = 0,
        ?Model $reference = null,
        ?StockBatch $stockBatch = null,
        ?string $reason = null
    ): StockLedger {
        if (! $product->is_active) {
            throw new InvalidArgumentException('Inactive products cannot be posted to stock.');
        }

        if ($unit->product_id !== $product->id) {
            throw new InvalidArgumentException('The selected unit does not belong to the product.');
        }

        if ((float) $quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than zero.');
        }

        $ledger = StockLedger::create([
            'product_id' => $product->id,
            'stock_batch_id' => $stockBatch?->id,
            'product_unit_id' => $unit->id,
            'type' => $type,
            'direction' => $direction,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'reference_type' => $reference ? $reference::class : null,
            'reference_id' => $reference?->getKey(),
            'reason' => $reason,
            'created_by' => Auth::id(),
        ]);

        $balance = StockBalance::firstOrCreate([
            'product_id' => $product->id,
            'stock_batch_id' => $stockBatch?->id,
            'product_unit_id' => $unit->id,
        ], [
            'quantity' => 0,
        ]);

        $signedQuantity = $direction === StockLedger::DIRECTION_OUT ? -1 * (float) $quantity : (float) $quantity;
        $balance->update([
            'quantity' => (float) $balance->quantity + $signedQuantity,
        ]);

        return $ledger;
    }

    private function resolveBatch(Product $product, array $batchData): ?StockBatch
    {
        if (! $product->track_batch && ! $product->track_expiry) {
            return null;
        }

        if ($product->track_batch && empty($batchData['batch_number'])) {
            throw new InvalidArgumentException('Batch number is required for this product.');
        }

        if ($product->track_expiry && empty($batchData['expires_at'])) {
            throw new InvalidArgumentException('Expiry date is required for this product.');
        }

        return StockBatch::firstOrCreate([
            'product_id' => $product->id,
            'batch_number' => $batchData['batch_number'] ?? null,
            'expires_at' => $batchData['expires_at'] ?? null,
        ], [
            'received_at' => $batchData['received_at'] ?? now()->toDateString(),
        ]);
    }
}
