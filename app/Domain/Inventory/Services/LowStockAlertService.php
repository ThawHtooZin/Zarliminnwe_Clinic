<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Units\Services\UnitRelationshipService;
use App\Models\Product;
use Illuminate\Support\Collection;

class LowStockAlertService
{
    public function __construct(private readonly UnitRelationshipService $unitRelationshipService) {}

    public function getLowStockProducts(array $filters = []): Collection
    {
        return Product::query()
            ->with(['category', 'reorderUnit', 'stockBalances.productUnit', 'stockBalances.stockBatch'])
            ->whereNotNull('reorder_product_unit_id')
            ->whereNotNull('reorder_quantity')
            ->whereHas('reorderUnit', function ($query): void {
                $query->whereColumn('product_units.product_id', 'products.id');
            })
            ->when($filters['category_id'] ?? null, function ($query, mixed $categoryId): void {
                $query->where('product_category_id', $categoryId);
            })
            ->when($filters['product_id'] ?? null, function ($query, mixed $productId): void {
                $query->where('id', $productId);
            })
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('generic_name', 'like', "%{$search}%");
                });
            })
            ->when($filters['active_status'] ?? null, function ($query, string $activeStatus): void {
                if ($activeStatus === 'active') {
                    $query->where('is_active', true);
                }

                if ($activeStatus === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product): array => $this->buildAlertRow($product))
            ->filter(fn (array $row): bool => $row['available_quantity'] < $row['reorder_quantity'])
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAlertRow(Product $product): array
    {
        $reorderUnit = $product->reorderUnit;
        $balances = $product->stockBalances;
        $availableQuantity = $balances->sum(fn ($balance): float => (float) $this->unitRelationshipService->convert(
            $balance->productUnit,
            $reorderUnit,
            $balance->quantity
        ));
        $reorderQuantity = (float) $product->reorder_quantity;
        $shortageQuantity = max($reorderQuantity - $availableQuantity, 0);

        return [
            'product' => $product,
            'reorder_unit' => $reorderUnit,
            'reorder_quantity' => $reorderQuantity,
            'available_quantity' => $availableQuantity,
            'formatted_stock' => $this->unitRelationshipService->formatStock($product, $balances),
            'formatted_available_stock' => $this->formatQuantity($availableQuantity).' '.$reorderUnit->abbreviation,
            'shortage_quantity' => $shortageQuantity,
            'formatted_shortage' => $this->formatQuantity($shortageQuantity).' '.$reorderUnit->abbreviation,
        ];
    }

    private function formatQuantity(float $quantity): string
    {
        $formatted = number_format($quantity, 6, '.', '');

        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    }
}
