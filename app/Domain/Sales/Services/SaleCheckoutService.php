<?php

namespace App\Domain\Sales\Services;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Inventory\Services\StockPostingService;
use App\Domain\Units\Services\UnitRelationshipService;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SaleCheckoutService
{
    public function __construct(
        private readonly SaleNumberGenerator $saleNumberGenerator,
        private readonly UnitRelationshipService $unitRelationshipService,
        private readonly StockPostingService $stockPostingService,
        private readonly AuditLogger $auditLogger
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $cartLines
     * @param  array<string, mixed>  $paymentData
     */
    public function checkout(User $cashier, array $cartLines, array $paymentData): Sale
    {
        return DB::transaction(function () use ($cashier, $cartLines, $paymentData): Sale {
            $lineData = $this->normalizeLines($cartLines);
            $totals = $this->calculateTotals($lineData, $paymentData);

            $sale = Sale::create([
                'sale_number' => $this->saleNumberGenerator->generate(),
                'patient_visit_id' => $paymentData['patient_visit_id'] ?? null,
                'status' => Sale::STATUS_DRAFT,
                'payment_method' => $paymentData['payment_method'] ?? Sale::PAYMENT_CASH,
                'notes' => $paymentData['notes'] ?? null,
            ]);

            foreach ($lineData as $line) {
                $sale->lines()->create([
                    'product_id' => $line['product']->id,
                    'product_unit_id' => $line['unit']->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'line_total' => $line['line_total'],
                ]);
            }

            return $this->completeSale($sale, $totals, $cashier);
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $cartLines
     * @param  array<string, mixed>  $paymentData
     */
    public function completeHeldSale(Sale $sale, User $cashier, array $cartLines, array $paymentData): Sale
    {
        if ($sale->status !== Sale::STATUS_HELD) {
            throw new InvalidArgumentException('Only held sales can be resumed for checkout.');
        }

        return DB::transaction(function () use ($sale, $cashier, $cartLines, $paymentData): Sale {
            $lineData = $this->normalizeLines($cartLines);
            $totals = $this->calculateTotals($lineData, $paymentData);

            $sale->lines()->delete();

            foreach ($lineData as $line) {
                $sale->lines()->create([
                    'product_id' => $line['product']->id,
                    'product_unit_id' => $line['unit']->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'line_total' => $line['line_total'],
                ]);
            }

            return $this->completeSale($sale->fresh(), $totals, $cashier);
        });
    }

    /**
     * @param  array<string, mixed>  $paymentData
     */
    public function completeSale(Sale $sale, array $paymentData, User $cashier): Sale
    {
        if (! $sale->isCompletable()) {
            throw new InvalidArgumentException('Only draft or held sales can be completed.');
        }

        $sale->load(['lines.product', 'lines.productUnit']);

        if ($sale->lines->isEmpty()) {
            throw new InvalidArgumentException('Sale must have at least one line.');
        }

        foreach ($sale->lines as $line) {
            $this->validateSaleLine($line->product, $line->productUnit, (float) $line->quantity, (float) $line->unit_price);

            $balances = StockBalance::query()
                ->with(['productUnit', 'stockBatch'])
                ->where('product_id', $line->product_id)
                ->where('quantity', '>', 0)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $deductions = $this->unitRelationshipService->calculateDeduction($balances, $line->productUnit, $line->quantity);

            foreach ($deductions as $deduction) {
                $deductionData = (array) $deduction;
                /** @var StockBalance $balance */
                $balance = $deductionData['balance'];

                $this->stockPostingService->postMovement(
                    product: $line->product,
                    unit: $balance->productUnit,
                    quantity: $deductionData['quantity'],
                    type: StockLedger::TYPE_SALE,
                    direction: StockLedger::DIRECTION_OUT,
                    unitCost: 0,
                    reference: $sale,
                    stockBatch: $balance->stockBatch,
                    reason: 'Sale '.$sale->sale_number
                );
            }
        }

        $sale->update([
            'status' => Sale::STATUS_COMPLETED,
            'subtotal' => $paymentData['subtotal'],
            'discount_total' => $paymentData['discount_total'],
            'tax_total' => $paymentData['tax_total'],
            'grand_total' => $paymentData['grand_total'],
            'amount_paid' => $paymentData['amount_paid'],
            'change_amount' => $paymentData['change_amount'],
            'payment_method' => $paymentData['payment_method'],
            'sold_by' => $cashier->id,
            'sold_at' => now(),
        ]);

        $sale = $sale->fresh()->load(['lines.product', 'lines.productUnit']);
        $this->auditLogger->log('sale.completed', $sale, null, $sale->toArray());

        return $sale;
    }

    /**
     * @param  array<int, array<string, mixed>>  $cartLines
     * @return array<int, array<string, mixed>>
     */
    private function normalizeLines(array $cartLines): array
    {
        if ($cartLines === []) {
            throw new InvalidArgumentException('Sale must have at least one line.');
        }

        $lineData = [];

        foreach ($cartLines as $cartLine) {
            $productId = $cartLine['productId'] ?? $cartLine['product_id'] ?? null;
            $unitId = $cartLine['unitId'] ?? $cartLine['product_unit_id'] ?? null;
            $quantity = (float) ($cartLine['quantity'] ?? 0);
            $unitPrice = (float) ($cartLine['unitPrice'] ?? $cartLine['unit_price'] ?? 0);

            $product = Product::find($productId);
            $unit = ProductUnit::find($unitId);

            if (! $product || ! $unit) {
                throw new InvalidArgumentException('Each sale line requires a valid product and unit.');
            }

            $this->validateSaleLine($product, $unit, $quantity, $unitPrice);

            $lineData[] = [
                'product' => $product,
                'unit' => $unit,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $quantity * $unitPrice,
            ];
        }

        return $lineData;
    }

    private function validateSaleLine(Product $product, ProductUnit $unit, float $quantity, float $unitPrice): void
    {
        if (! $product->is_active) {
            throw new InvalidArgumentException('Inactive products cannot be sold.');
        }

        if ($unit->product_id !== $product->id) {
            throw new InvalidArgumentException('The selected sale unit does not belong to the selected product.');
        }

        if (! $unit->is_sale_unit) {
            throw new InvalidArgumentException('The selected unit is not enabled for sale.');
        }

        if ($quantity <= 0) {
            throw new InvalidArgumentException('Sale quantity must be greater than zero.');
        }

        if ($unitPrice < 0) {
            throw new InvalidArgumentException('Unit price cannot be negative.');
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $lineData
     * @param  array<string, mixed>  $paymentData
     * @return array<string, mixed>
     */
    private function calculateTotals(array $lineData, array $paymentData): array
    {
        $subtotal = array_sum(array_column($lineData, 'line_total'));
        $discountTotal = (float) ($paymentData['discount_total'] ?? 0);
        $taxTotal = (float) ($paymentData['tax_total'] ?? 0);
        $grandTotal = max($subtotal - $discountTotal + $taxTotal, 0);
        $amountPaid = (float) ($paymentData['amount_paid'] ?? 0);
        $paymentMethod = $paymentData['payment_method'] ?? Sale::PAYMENT_CASH;

        if ($discountTotal < 0 || $taxTotal < 0) {
            throw new InvalidArgumentException('Discount and tax cannot be negative.');
        }

        if ($discountTotal > $subtotal) {
            throw new InvalidArgumentException('Discount cannot exceed sale subtotal.');
        }

        if ($amountPaid < $grandTotal) {
            throw new InvalidArgumentException('Amount paid must be greater than or equal to grand total.');
        }

        if (! in_array($paymentMethod, [Sale::PAYMENT_CASH, Sale::PAYMENT_CARD, Sale::PAYMENT_MIXED, Sale::PAYMENT_OTHER], true)) {
            throw new InvalidArgumentException('Invalid payment method.');
        }

        return [
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
            'amount_paid' => $amountPaid,
            'change_amount' => $amountPaid - $grandTotal,
            'payment_method' => $paymentMethod,
        ];
    }
}
