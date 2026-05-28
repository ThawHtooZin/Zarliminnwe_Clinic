<?php

namespace App\Domain\Units\Services;

use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\StockBalance;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class UnitRelationshipService
{
    public function convert(ProductUnit $fromUnit, ProductUnit $toUnit, float|int|string $quantity): string
    {
        $this->ensureSameProduct($fromUnit, $toUnit);

        $fromRootFactor = $this->factorToTopUnit($fromUnit);
        $toRootFactor = $this->factorToTopUnit($toUnit);
        $converted = (float) $quantity * $fromRootFactor / $toRootFactor;

        return $this->normalizeQuantity($converted);
    }

    public function canConvert(ProductUnit $fromUnit, ProductUnit $toUnit): bool
    {
        try {
            $this->ensureSameProduct($fromUnit, $toUnit);
            $this->factorToTopUnit($fromUnit);
            $this->factorToTopUnit($toUnit);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * @deprecated POS checkout uses calculateIntegerSaleFulfillment() instead of fractional cross-unit deduction.
     */
    public function calculateDeduction(Collection $availableBalances, ProductUnit $saleUnit, float|int|string $quantity): Collection
    {
        $remaining = (float) $quantity;
        $deductions = collect();

        foreach ($availableBalances as $balance) {
            if ($remaining <= 0) {
                break;
            }

            $availableInSaleUnit = (float) $this->convert($balance->productUnit, $saleUnit, $balance->quantity);

            if ($availableInSaleUnit <= 0) {
                continue;
            }

            $saleUnitDeduction = min($remaining, $availableInSaleUnit);
            $balanceUnitDeduction = $this->convert($saleUnit, $balance->productUnit, $saleUnitDeduction);

            $deductions->push([
                'balance' => $balance,
                'product_unit_id' => $balance->product_unit_id,
                'quantity' => $balanceUnitDeduction,
            ]);

            $remaining -= $saleUnitDeduction;
        }

        if ($remaining > 0.000001) {
            throw new InvalidArgumentException('Insufficient stock for the selected unit.');
        }

        return $deductions;
    }

    public function maxIntegerSellableQuantity(Collection $balances, ProductUnit $saleUnit): int
    {
        $saleUnit->loadMissing('parent');

        $total = 0;

        foreach ($balances as $balance) {
            $quantity = $this->integerBalanceQuantity($balance);

            if ($quantity <= 0) {
                continue;
            }

            if ($balance->product_unit_id === $saleUnit->id) {
                $total += $quantity;

                continue;
            }

            $balance->loadMissing('productUnit');

            if ($this->isAncestorUnit($balance->productUnit, $saleUnit)) {
                $total += $this->convertAncestorQuantityToSaleUnit($balance->productUnit, $saleUnit, $quantity);
            }
        }

        return $total;
    }

    /**
     * Build integer-only fulfillment steps: direct sale-unit OUT rows, or auto-unpack (1 parent OUT, child IN) then sale OUT.
     *
     * @return Collection<int, array{
     *   action: 'sale_out'|'unpack_out'|'unpack_in',
     *   balance: StockBalance,
     *   product_unit_id: int,
     *   quantity: int,
     *   sale_unit_quantity: int|null
     * }>
     */
    public function calculateIntegerSaleFulfillment(
        Collection $balances,
        ProductUnit $saleUnit,
        float|int|string $quantity
    ): Collection {
        $remaining = $this->normalizeIntegerQuantity($quantity);

        if ($remaining <= 0) {
            throw new InvalidArgumentException('Sale quantity must be a positive whole number.');
        }

        $saleUnit->loadMissing('parent');
        $workingBalances = $this->indexBalances($balances);
        $steps = collect();

        while ($remaining > 0) {
            $deducted = $this->appendIntegerSaleOutSteps($workingBalances, $saleUnit, $remaining, $steps);
            $remaining -= $deducted;

            if ($remaining <= 0) {
                break;
            }

            if (! $saleUnit->parent_product_unit_id) {
                throw new InvalidArgumentException('Insufficient stock for the selected unit.');
            }

            $this->appendSingleUnpackSteps($workingBalances, $saleUnit, $steps);
        }

        return $steps;
    }

    public function formatStock(Product $product, Collection $balances, ?ProductUnit $preferredUnit = null): string
    {
        if ($balances->isEmpty()) {
            return '0';
        }

        if ($preferredUnit) {
            $total = $balances->sum(fn ($balance): float => (float) $this->convert(
                $balance->productUnit,
                $preferredUnit,
                $balance->quantity
            ));

            return $this->normalizeQuantity($total).' '.$preferredUnit->abbreviation;
        }

        return $balances
            ->groupBy('product_unit_id')
            ->map(function (Collection $unitBalances): string {
                $unit = $unitBalances->first()->productUnit;
                $quantity = $unitBalances->sum(fn ($balance): float => (float) $balance->quantity);

                return $this->normalizeQuantity($quantity).' '.$unit->abbreviation;
            })
            ->values()
            ->implode(', ');
    }

    public function validateProductUnits(Collection $units): void
    {
        if ($units->isEmpty()) {
            throw new InvalidArgumentException('At least one unit is required.');
        }

        foreach ($units as $unit) {
            if ($unit->parent_product_unit_id && (float) $unit->conversion_factor <= 0) {
                throw new InvalidArgumentException('Related units require a positive conversion factor.');
            }

            $visited = [];
            $current = $unit;

            while ($current?->parent_product_unit_id) {
                if (in_array($current->id, $visited, true)) {
                    throw new InvalidArgumentException('Unit relationships cannot be circular.');
                }

                $visited[] = $current->id;
                $current = $units->firstWhere('id', $current->parent_product_unit_id) ?? $current->parent;
            }
        }
    }

    private function appendIntegerSaleOutSteps(
        array &$workingBalances,
        ProductUnit $saleUnit,
        int $remaining,
        Collection $steps
    ): int {
        $deducted = 0;

        foreach ($this->sortedBalancesForUnit($workingBalances, $saleUnit->id) as $balance) {
            if ($remaining <= 0) {
                break;
            }

            $available = $this->integerBalanceQuantity($balance);

            if ($available <= 0) {
                continue;
            }

            $take = min($remaining, $available);
            $this->decrementWorkingBalance($workingBalances, $balance, $take);

            $steps->push([
                'action' => 'sale_out',
                'balance' => $balance,
                'product_unit_id' => $saleUnit->id,
                'quantity' => $take,
                'sale_unit_quantity' => $take,
            ]);

            $remaining -= $take;
            $deducted += $take;
        }

        return $deducted;
    }

    /**
     * @param  array<int, StockBalance>  $workingBalances
     */
    private function appendSingleUnpackSteps(array &$workingBalances, ProductUnit $childUnit, Collection $steps): void
    {
        $childUnit->loadMissing('parent');
        $parentUnit = $childUnit->parent;

        if (! $parentUnit) {
            throw new InvalidArgumentException('Insufficient stock for the selected unit.');
        }

        $parentBalance = $this->firstBalanceWithIntegerStock($workingBalances, $parentUnit->id);

        if (! $parentBalance && $parentUnit->parent_product_unit_id) {
            $this->appendSingleUnpackSteps($workingBalances, $parentUnit, $steps);
            $parentBalance = $this->firstBalanceWithIntegerStock($workingBalances, $parentUnit->id);
        }

        if (! $parentBalance) {
            throw new InvalidArgumentException('Insufficient stock for the selected unit.');
        }

        $childPerParent = $this->integerConversionFactor($childUnit);

        $this->decrementWorkingBalance($workingBalances, $parentBalance, 1);

        $steps->push([
            'action' => 'unpack_out',
            'balance' => $parentBalance,
            'product_unit_id' => $parentUnit->id,
            'quantity' => 1,
            'sale_unit_quantity' => null,
        ]);

        $childBalance = $this->incrementWorkingBalance(
            $workingBalances,
            $childUnit,
            $parentBalance,
            $childPerParent
        );

        $steps->push([
            'action' => 'unpack_in',
            'balance' => $childBalance,
            'product_unit_id' => $childUnit->id,
            'quantity' => $childPerParent,
            'sale_unit_quantity' => null,
        ]);
    }

    /**
     * @param  array<int, StockBalance>  $workingBalances
     * @return array<int, StockBalance>
     */
    private function sortedBalancesForUnit(array $workingBalances, int $productUnitId): array
    {
        return collect($workingBalances)
            ->filter(fn (StockBalance $balance): bool => $balance->product_unit_id === $productUnitId)
            ->sortBy('id')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, StockBalance>  $balances
     * @return array<int, StockBalance>
     */
    private function indexBalances(Collection $balances): array
    {
        $indexed = [];

        foreach ($balances as $balance) {
            $indexed[$balance->id] = $balance;
        }

        return $indexed;
    }

    private function firstBalanceWithIntegerStock(array $workingBalances, int $productUnitId): ?StockBalance
    {
        foreach ($this->sortedBalancesForUnit($workingBalances, $productUnitId) as $balance) {
            if ($this->integerBalanceQuantity($balance) >= 1) {
                return $balance;
            }
        }

        return null;
    }

    private function integerBalanceQuantity(StockBalance $balance): int
    {
        return max(0, (int) floor((float) $balance->quantity));
    }

    private function normalizeIntegerQuantity(float|int|string $quantity): int
    {
        $value = (float) $quantity;

        if ($value <= 0 || abs($value - round($value)) > 0.000001) {
            throw new InvalidArgumentException('Sale quantity must be a positive whole number.');
        }

        return (int) round($value);
    }

    private function integerConversionFactor(ProductUnit $unit): int
    {
        $factor = (int) round((float) $unit->conversion_factor);

        if ($factor < 1) {
            throw new InvalidArgumentException('Conversion factor must be a positive whole number for integer stock operations.');
        }

        return $factor;
    }

    private function isAncestorUnit(ProductUnit $ancestor, ProductUnit $descendant): bool
    {
        $current = $descendant;
        $current->loadMissing('parent');

        while ($current->parent_product_unit_id) {
            if ($current->parent_product_unit_id === $ancestor->id) {
                return true;
            }

            $current = $current->parent ?? ProductUnit::query()->find($current->parent_product_unit_id);

            if (! $current) {
                return false;
            }

            $current->loadMissing('parent');
        }

        return false;
    }

    private function convertAncestorQuantityToSaleUnit(ProductUnit $ancestor, ProductUnit $saleUnit, int $ancestorQuantity): int
    {
        $factor = 1;
        $current = $saleUnit;
        $current->loadMissing('parent');

        while ($current->id !== $ancestor->id) {
            $factor *= $this->integerConversionFactor($current);
            $current = $current->parent;

            if (! $current) {
                throw new InvalidArgumentException('Invalid unit hierarchy for stock conversion.');
            }

            $current->loadMissing('parent');
        }

        return $ancestorQuantity * $factor;
    }

    /**
     * @param  array<int, StockBalance>  $workingBalances
     */
    private function decrementWorkingBalance(array &$workingBalances, StockBalance $balance, int $quantity): void
    {
        $balance->quantity = $this->integerBalanceQuantity($balance) - $quantity;
        $workingBalances[$balance->id] = $balance;
    }

    /**
     * @param  array<int, StockBalance>  $workingBalances
     */
    private function incrementWorkingBalance(
        array &$workingBalances,
        ProductUnit $childUnit,
        StockBalance $parentBalance,
        int $quantity
    ): StockBalance {
        foreach ($workingBalances as $balance) {
            if (
                $balance->product_unit_id === $childUnit->id
                && $balance->product_id === $parentBalance->product_id
                && $balance->stock_batch_id === $parentBalance->stock_batch_id
            ) {
                $balance->quantity = $this->integerBalanceQuantity($balance) + $quantity;
                $workingBalances[$balance->id] = $balance;

                return $balance;
            }
        }

        $childBalance = new StockBalance([
            'product_id' => $parentBalance->product_id,
            'stock_batch_id' => $parentBalance->stock_batch_id,
            'product_unit_id' => $childUnit->id,
            'quantity' => $quantity,
        ]);

        if ($parentBalance->exists) {
            $childBalance->setRelation('stockBatch', $parentBalance->stockBatch);
        }

        $childBalance->setRelation('productUnit', $childUnit);
        $workingBalances['sim-'.$childUnit->id.'-'.$parentBalance->stock_batch_id] = $childBalance;

        return $childBalance;
    }

    private function factorToTopUnit(ProductUnit $unit): float
    {
        $factor = 1.0;
        $current = $unit;
        $visited = [];

        while ($current->parent_product_unit_id) {
            if (in_array($current->id, $visited, true)) {
                throw new InvalidArgumentException('Circular unit relationship detected.');
            }

            if ((float) $current->conversion_factor <= 0) {
                throw new InvalidArgumentException('Conversion factor must be positive.');
            }

            $visited[] = $current->id;
            $factor /= (float) $current->conversion_factor;
            $current = $current->parent;
        }

        return $factor;
    }

    private function ensureSameProduct(ProductUnit $fromUnit, ProductUnit $toUnit): void
    {
        if ($fromUnit->product_id !== $toUnit->product_id) {
            throw new InvalidArgumentException('Units must belong to the same product.');
        }
    }

    private function normalizeQuantity(float $quantity): string
    {
        $formatted = number_format($quantity, 6, '.', '');

        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    }
}
