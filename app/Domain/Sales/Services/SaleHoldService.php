<?php

namespace App\Domain\Sales\Services;

use App\Domain\Audit\Services\AuditLogger;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SaleHoldService
{
    public function __construct(
        private readonly SaleNumberGenerator $saleNumberGenerator,
        private readonly AuditLogger $auditLogger
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $cartLines
     * @param  array<string, mixed>  $saleData
     */
    public function hold(User $cashier, array $cartLines, array $saleData = [], ?Sale $sale = null): Sale
    {
        return DB::transaction(function () use ($cashier, $cartLines, $saleData, $sale): Sale {
            $lineData = $this->normalizeLines($cartLines);
            $totals = $this->calculateTotals($lineData, $saleData);

            if ($sale && $sale->status !== Sale::STATUS_HELD) {
                throw new InvalidArgumentException('Only held sales can be updated as held.');
            }

            $sale = $sale ?: new Sale([
                'sale_number' => $this->saleNumberGenerator->generate(),
            ]);

            $sale->fill([
                'patient_visit_record_id' => $saleData['patient_visit_record_id'] ?? $saleData['patient_visit_id'] ?? null,
                'status' => Sale::STATUS_HELD,
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'tax_total' => $totals['tax_total'],
                'grand_total' => $totals['grand_total'],
                'amount_paid' => 0,
                'change_amount' => 0,
                'payment_method' => $saleData['payment_method'] ?? Sale::PAYMENT_CASH,
                'sold_by' => $cashier->id,
            ]);
            $sale->save();

            $sale->lines()->delete();

            foreach ($lineData as $line) {
                $sale->lines()->create([
                    'product_id' => $line['product']->id,
                    'product_unit_id' => $line['unit']->id,
                    'use_parent_breakdown' => $line['use_parent_breakdown'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'line_total' => $line['line_total'],
                ]);
            }

            $sale = $sale->fresh()->load(['lines.product', 'lines.productUnit']);
            $this->auditLogger->log('sale.held', $sale, null, $sale->toArray());

            return $sale;
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $cartLines
     * @return array<int, array<string, mixed>>
     */
    private function normalizeLines(array $cartLines): array
    {
        if ($cartLines === []) {
            throw new InvalidArgumentException('Held sale must have at least one line.');
        }

        $lineData = [];

        foreach ($cartLines as $cartLine) {
            $productId = $cartLine['productId'] ?? $cartLine['product_id'] ?? null;
            $unitId = $cartLine['unitId'] ?? $cartLine['product_unit_id'] ?? null;
            $quantity = (int) round((float) ($cartLine['quantity'] ?? 0));
            $unitPrice = (float) ($cartLine['unitPrice'] ?? $cartLine['unit_price'] ?? 0);

            $product = Product::find($productId);
            $unit = ProductUnit::find($unitId);

            if (! $product || ! $unit) {
                throw new InvalidArgumentException('Each held sale line requires a valid product and unit.');
            }

            if (! $product->is_active) {
                throw new InvalidArgumentException('Inactive products cannot be held for sale.');
            }

            if ($unit->product_id !== $product->id) {
                throw new InvalidArgumentException('The selected sale unit does not belong to the selected product.');
            }

            if (! $unit->is_sale_unit) {
                throw new InvalidArgumentException('The selected unit is not enabled for sale.');
            }

            if ($quantity <= 0) {
                throw new InvalidArgumentException('Held sale quantity must be greater than zero.');
            }

            if ($unitPrice < 0) {
                throw new InvalidArgumentException('Unit price cannot be negative.');
            }

            $lineData[] = [
                'product' => $product,
                'unit' => $unit,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $quantity * $unitPrice,
                'use_parent_breakdown' => false,
            ];
        }

        return $lineData;
    }

    /**
     * @param  array<int, array<string, mixed>>  $lineData
     * @param  array<string, mixed>  $saleData
     * @return array<string, float>
     */
    private function calculateTotals(array $lineData, array $saleData): array
    {
        $subtotal = array_sum(array_column($lineData, 'line_total'));
        $discountTotal = (float) ($saleData['discount_total'] ?? 0);
        $taxTotal = (float) ($saleData['tax_total'] ?? 0);

        if ($discountTotal < 0 || $taxTotal < 0) {
            throw new InvalidArgumentException('Discount and tax cannot be negative.');
        }

        if ($discountTotal > $subtotal) {
            throw new InvalidArgumentException('Discount cannot exceed sale subtotal.');
        }

        return [
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'grand_total' => max($subtotal - $discountTotal + $taxTotal, 0),
        ];
    }
}
