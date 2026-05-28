<?php

namespace App\Domain\Sales\Services;

use App\Domain\Units\Services\UnitRelationshipService;
use App\Models\ProductUnit;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class PosStockAvailabilityService
{
    public function __construct(private readonly UnitRelationshipService $unitRelationshipService) {}

    /**
     * @return array{
     *   has_direct_stock: bool,
     *   has_parent_breakdown: bool,
     *   is_available: bool,
     *   direct_qty: int,
     *   max_qty: int
     * }
     */
    public function availabilityForUnit(Collection $balances, ProductUnit $saleUnit): array
    {
        $saleUnit->loadMissing('parent');

        $directQty = (int) $balances
            ->where('product_unit_id', $saleUnit->id)
            ->sum(fn ($balance): int => max(0, (int) floor((float) $balance->quantity)));

        $maxQty = $this->unitRelationshipService->maxIntegerSellableQuantity($balances, $saleUnit);

        return [
            'has_direct_stock' => $directQty > 0,
            'has_parent_breakdown' => ($maxQty - $directQty) > 0,
            'is_available' => $maxQty > 0,
            'direct_qty' => $directQty,
            'max_qty' => $maxQty,
        ];
    }

    /**
     * @return Collection<int, array{
     *   action: 'sale_out'|'unpack_out'|'unpack_in',
     *   balance: \App\Models\StockBalance,
     *   product_unit_id: int,
     *   quantity: int,
     *   sale_unit_quantity: int|null
     * }>
     */
    public function calculateFulfillmentSteps(
        Collection $balances,
        ProductUnit $saleUnit,
        float $quantity
    ): Collection {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Sale quantity must be greater than zero.');
        }

        return $this->unitRelationshipService->calculateIntegerSaleFulfillment(
            $balances,
            $saleUnit,
            $quantity
        );
    }
}
