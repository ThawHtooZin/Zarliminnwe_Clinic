<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Units\Services\UnitRelationshipService;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockLedger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class InventoryReportService
{
    public function __construct(
        private readonly UnitRelationshipService $unitRelationshipService,
        private readonly LowStockAlertService $lowStockAlertService,
        private readonly ExpiryAlertService $expiryAlertService
    ) {}

    public function stockOnHand(array $filters = []): Builder
    {
        $query = StockBalance::query()->with(['product.category', 'productUnit', 'stockBatch']);

        if ($filters['product_id'] ?? null) {
            $query->where('product_id', $filters['product_id']);
        }

        if ($filters['batch_id'] ?? null) {
            $query->where('stock_batch_id', $filters['batch_id']);
        }

        if ($filters['category_id'] ?? null) {
            $query->whereHas('product', fn ($query) => $query->where('product_category_id', $filters['category_id']));
        }

        if (($filters['active_status'] ?? null) === 'active') {
            $query->whereHas('product', fn ($query) => $query->where('is_active', true));
        }

        if (($filters['active_status'] ?? null) === 'inactive') {
            $query->whereHas('product', fn ($query) => $query->where('is_active', false));
        }

        if ($filters['search'] ?? null) {
            $search = $filters['search'];
            $query->whereHas('product', function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('generic_name', 'like', "%{$search}%");
            });
        }

        $query->orderBy('product_id')
            ->orderBy('stock_batch_id')
            ->orderBy('product_unit_id');

        return $query;
    }

    public function stockMovements(array $filters = []): Builder
    {
        return StockLedger::query()
            ->with(['product.category', 'productUnit', 'stockBatch', 'creator', 'reference'])
            ->when($filters['product_id'] ?? null, fn ($query, mixed $productId) => $query->where('product_id', $productId))
            ->when($filters['type'] ?? null, fn ($query, string $type) => $query->where('type', $type))
            ->when($filters['direction'] ?? null, fn ($query, string $direction) => $query->where('direction', $direction))
            ->when($filters['date_from'] ?? null, fn ($query, string $dateFrom) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($filters['date_to'] ?? null, fn ($query, string $dateTo) => $query->whereDate('created_at', '<=', $dateTo))
            ->latest();
    }

    public function lowStock(array $filters = []): Collection
    {
        return $this->lowStockAlertService->getLowStockProducts($filters);
    }

    public function expiry(array $filters = []): Collection
    {
        return $this->expiryAlertService->getExpiringBatches((int) ($filters['days'] ?? 90), $filters);
    }

    public function adjustments(array $filters = []): Builder
    {
        return StockLedger::query()
            ->with(['product.category', 'productUnit', 'stockBatch', 'creator', 'reference'])
            ->where('type', StockLedger::TYPE_ADJUSTMENT)
            ->when($filters['product_id'] ?? null, fn ($query, mixed $productId) => $query->where('product_id', $productId))
            ->when($filters['batch_id'] ?? null, fn ($query, mixed $batchId) => $query->where('stock_batch_id', $batchId))
            ->when($filters['direction'] ?? null, fn ($query, string $direction) => $query->where('direction', $direction))
            ->when($filters['date_from'] ?? null, fn ($query, string $dateFrom) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($filters['date_to'] ?? null, fn ($query, string $dateTo) => $query->whereDate('created_at', '<=', $dateTo))
            ->latest();
    }

    public function formattedStock(Product $product): string
    {
        return $this->unitRelationshipService->formatStock($product, $product->stockBalances);
    }
}
