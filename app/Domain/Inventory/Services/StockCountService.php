<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Audit\Services\AuditLogger;
use App\Models\StockBalance;
use App\Models\StockCount;
use App\Models\StockLedger;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockCountService
{
    public function __construct(
        private readonly StockCountNumberGenerator $numberGenerator,
        private readonly StockPostingService $stockPostingService,
        private readonly AuditLogger $auditLogger
    ) {}

    /**
     * @param  array<int>  $stockBalanceIds
     */
    public function createDraftFromBalances(array $stockBalanceIds, User $user, ?string $notes = null): StockCount
    {
        $stockBalanceIds = array_values(array_unique(array_map('intval', $stockBalanceIds)));

        if ($stockBalanceIds === []) {
            throw new InvalidArgumentException('At least one stock balance must be selected.');
        }

        return DB::transaction(function () use ($stockBalanceIds, $user, $notes): StockCount {
            $balances = StockBalance::with(['product', 'productUnit', 'stockBatch'])
                ->whereIn('id', $stockBalanceIds)
                ->orderBy('id')
                ->get();

            if ($balances->count() !== count($stockBalanceIds)) {
                throw new InvalidArgumentException('One or more selected stock balances could not be found.');
            }

            $stockCount = StockCount::create([
                'count_number' => $this->numberGenerator->generate(),
                'status' => StockCount::STATUS_DRAFT,
                'counted_by' => $user->id,
                'started_at' => now(),
                'notes' => $notes,
            ]);

            foreach ($balances as $balance) {
                $stockCount->lines()->create([
                    'product_id' => $balance->product_id,
                    'stock_batch_id' => $balance->stock_batch_id,
                    'product_unit_id' => $balance->product_unit_id,
                    'expected_quantity' => $balance->quantity,
                    'counted_quantity' => $balance->quantity,
                    'variance_quantity' => 0,
                ]);
            }

            $this->auditLogger->log('stock_count.created', $stockCount, null, $stockCount->load('lines')->toArray());

            return $stockCount;
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    public function updateDraftLines(StockCount $stockCount, array $lines): StockCount
    {
        if (! $stockCount->isDraft()) {
            throw new InvalidArgumentException('Only draft stock counts can be updated.');
        }

        return DB::transaction(function () use ($stockCount, $lines): StockCount {
            $stockCount->load('lines');
            $oldValues = $stockCount->toArray();

            foreach ($lines as $lineData) {
                $line = $stockCount->lines->firstWhere('id', (int) ($lineData['id'] ?? 0));

                if (! $line) {
                    throw new InvalidArgumentException('One or more stock count lines are invalid.');
                }

                $countedQuantity = (float) $lineData['counted_quantity'];

                if ($countedQuantity < 0) {
                    throw new InvalidArgumentException('Counted quantity must be zero or greater.');
                }

                $line->update([
                    'counted_quantity' => $countedQuantity,
                    'variance_quantity' => $countedQuantity - (float) $line->expected_quantity,
                    'notes' => $lineData['notes'] ?? null,
                ]);
            }

            $freshStockCount = $stockCount->fresh()->load('lines');
            $this->auditLogger->log('stock_count.updated', $freshStockCount, $oldValues, $freshStockCount->toArray());

            return $freshStockCount;
        });
    }

    public function submit(StockCount $stockCount, User $user): StockCount
    {
        if (! $stockCount->isDraft()) {
            throw new InvalidArgumentException('Only draft stock counts can be submitted.');
        }

        return DB::transaction(function () use ($stockCount): StockCount {
            $oldValues = $stockCount->toArray();
            $stockCount->update([
                'status' => StockCount::STATUS_SUBMITTED,
                'submitted_at' => now(),
            ]);

            $freshStockCount = $stockCount->fresh()->load('lines');
            $this->auditLogger->log('stock_count.submitted', $freshStockCount, $oldValues, $freshStockCount->toArray());

            return $freshStockCount;
        });
    }

    public function post(StockCount $stockCount, User $user): StockCount
    {
        if (! $stockCount->isSubmitted()) {
            throw new InvalidArgumentException('Only submitted stock counts can be posted.');
        }

        return DB::transaction(function () use ($stockCount, $user): StockCount {
            $stockCount = StockCount::with(['lines.product', 'lines.productUnit', 'lines.stockBatch'])
                ->lockForUpdate()
                ->findOrFail($stockCount->id);

            if (! $stockCount->isSubmitted()) {
                throw new InvalidArgumentException('Only submitted stock counts can be posted.');
            }

            $oldValues = $stockCount->toArray();

            foreach ($stockCount->lines as $line) {
                $this->lockLineBalance($line->product_id, $line->product_unit_id, $line->stock_batch_id);
            }

            foreach ($stockCount->lines as $line) {
                $variance = (float) $line->variance_quantity;

                if (abs($variance) < 0.000001) {
                    continue;
                }

                $ledger = $this->stockPostingService->postMovement(
                    product: $line->product,
                    unit: $line->productUnit,
                    quantity: abs($variance),
                    type: StockLedger::TYPE_ADJUSTMENT,
                    direction: $variance > 0 ? StockLedger::DIRECTION_IN : StockLedger::DIRECTION_OUT,
                    unitCost: 0,
                    reference: $stockCount,
                    stockBatch: $line->stockBatch,
                    reason: $this->adjustmentReason($stockCount, $line->notes)
                );

                $line->update(['adjustment_ledger_id' => $ledger->id]);
            }

            $stockCount->update([
                'status' => StockCount::STATUS_POSTED,
                'reviewed_by' => $user->id,
                'posted_at' => now(),
            ]);

            $freshStockCount = $stockCount->fresh()->load('lines.adjustmentLedger');
            $this->auditLogger->log('stock_count.posted', $freshStockCount, $oldValues, $freshStockCount->toArray());

            return $freshStockCount;
        });
    }

    public function cancel(StockCount $stockCount): StockCount
    {
        if (! $stockCount->isDraft() && ! $stockCount->isSubmitted()) {
            throw new InvalidArgumentException('Only draft or submitted stock counts can be cancelled.');
        }

        return DB::transaction(function () use ($stockCount): StockCount {
            $oldValues = $stockCount->toArray();
            $stockCount->update([
                'status' => StockCount::STATUS_CANCELLED,
            ]);

            $freshStockCount = $stockCount->fresh()->load('lines');
            $this->auditLogger->log('stock_count.cancelled', $freshStockCount, $oldValues, $freshStockCount->toArray());

            return $freshStockCount;
        });
    }

    private function lockLineBalance(int $productId, int $productUnitId, ?int $stockBatchId): void
    {
        StockBalance::query()
            ->where('product_id', $productId)
            ->where('product_unit_id', $productUnitId)
            ->when(
                $stockBatchId === null,
                fn ($query) => $query->whereNull('stock_batch_id'),
                fn ($query) => $query->where('stock_batch_id', $stockBatchId)
            )
            ->lockForUpdate()
            ->first();
    }

    private function adjustmentReason(StockCount $stockCount, ?string $lineNotes): string
    {
        $reason = 'Stock count variance '.$stockCount->count_number;

        if ($lineNotes) {
            $reason .= ': '.$lineNotes;
        }

        return $reason;
    }
}
