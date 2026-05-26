<?php

namespace App\Domain\Inventory\Services;

use App\Models\StockBalance;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class ExpiryAlertService
{
    public function getExpiringBatches(int $days = 90, array $filters = []): Collection
    {
        if ($days <= 0) {
            throw new InvalidArgumentException('Expiry window must be positive.');
        }

        $today = now()->startOfDay();
        $windowEnd = $today->copy()->addDays($days);

        return StockBalance::query()
            ->with(['product.category', 'productUnit', 'stockBatch'])
            ->where('quantity', '>', 0)
            ->whereHas('stockBatch', function ($query) use ($today, $windowEnd, $filters): void {
                $query->whereNotNull('expires_at')
                    ->whereDate('expires_at', '<=', ($filters['expired_only'] ?? false) ? $today : $windowEnd);
            })
            ->when($filters['product_id'] ?? null, function ($query, mixed $productId): void {
                $query->where('product_id', $productId);
            })
            ->when($filters['category_id'] ?? null, function ($query, mixed $categoryId): void {
                $query->whereHas('product', fn ($query) => $query->where('product_category_id', $categoryId));
            })
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->whereHas('product', function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('generic_name', 'like', "%{$search}%");
                });
            })
            ->get()
            ->map(fn (StockBalance $balance): array => $this->buildRow($balance, $today))
            ->filter(fn (array $row): bool => ! ($filters['expired_only'] ?? false) || $row['severity'] === 'expired')
            ->sortBy([
                ['expires_at', 'asc'],
                ['product.name', 'asc'],
            ])
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRow(StockBalance $balance, CarbonInterface $today): array
    {
        $expiresAt = $balance->stockBatch->expires_at->startOfDay();
        $daysUntilExpiry = (int) $today->diffInDays($expiresAt, false);

        return [
            'balance' => $balance,
            'product' => $balance->product,
            'stock_batch' => $balance->stockBatch,
            'product_unit' => $balance->productUnit,
            'batch_number' => $balance->stockBatch->batch_number,
            'expires_at' => $expiresAt,
            'days_until_expiry' => $daysUntilExpiry,
            'remaining_quantity' => (float) $balance->quantity,
            'formatted_remaining_quantity' => $this->formatQuantity((float) $balance->quantity).' '.$balance->productUnit->abbreviation,
            'severity' => $this->severity($daysUntilExpiry),
        ];
    }

    private function severity(int $daysUntilExpiry): string
    {
        if ($daysUntilExpiry < 0) {
            return 'expired';
        }

        if ($daysUntilExpiry <= 30) {
            return 'within_30_days';
        }

        if ($daysUntilExpiry <= 60) {
            return 'within_60_days';
        }

        return 'within_90_days';
    }

    private function formatQuantity(float $quantity): string
    {
        $formatted = number_format($quantity, 6, '.', '');

        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    }
}
