<?php

namespace App\Domain\Units\Services;

use App\Models\Product;
use App\Models\ProductUnit;
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
