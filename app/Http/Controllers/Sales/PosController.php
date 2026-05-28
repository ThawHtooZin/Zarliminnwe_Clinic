<?php

namespace App\Http\Controllers\Sales;

use App\Domain\Sales\Services\SaleCheckoutService;
use App\Domain\Sales\Services\SaleHoldService;
use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class PosController extends Controller
{
    public function __construct(
        private readonly SaleCheckoutService $saleCheckoutService,
        private readonly SaleHoldService $saleHoldService
    ) {}

    public function create(): View
    {
        return view('sales.pos', [
            'heldSale' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cart_payload' => ['required', 'json'],
            'held_sale_id' => ['nullable', 'exists:sales,id'],
            'patient_visit_record_id' => ['nullable', 'integer', 'exists:patient_visit_records,id'],
            'patient_visit_id' => ['nullable', 'integer', 'exists:patient_visit_records,id'],
            'discount_total' => ['nullable', 'numeric', 'min:0'],
            'tax_total' => ['nullable', 'numeric', 'min:0'],
            'amount_paid' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'string', 'in:'.implode(',', [
                Sale::PAYMENT_CASH,
                Sale::PAYMENT_CARD,
                Sale::PAYMENT_MIXED,
                Sale::PAYMENT_OTHER,
            ])],
        ]);

        $cartLines = $this->cartLinesFromPayload($validated['cart_payload']);

        try {
            $paymentData = [
                'patient_visit_record_id' => $validated['patient_visit_record_id'] ?? $validated['patient_visit_id'] ?? null,
                'discount_total' => $validated['discount_total'] ?? 0,
                'tax_total' => $validated['tax_total'] ?? 0,
                'amount_paid' => $validated['amount_paid'],
                'payment_method' => $validated['payment_method'],
            ];

            $sale = filled($validated['held_sale_id'] ?? null)
                ? $this->saleCheckoutService->completeHeldSale(Sale::findOrFail($validated['held_sale_id']), $request->user(), $cartLines, $paymentData)
                : $this->saleCheckoutService->checkout($request->user(), $cartLines, $paymentData);
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors(['checkout' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('sales.pos')
            ->with('status', 'Sale '.$sale->sale_number.' completed.');
    }

    public function hold(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cart_payload' => ['required', 'json'],
            'held_sale_id' => ['nullable', 'exists:sales,id'],
            'patient_visit_record_id' => ['nullable', 'integer', 'exists:patient_visit_records,id'],
            'patient_visit_id' => ['nullable', 'integer', 'exists:patient_visit_records,id'],
            'discount_total' => ['nullable', 'numeric', 'min:0'],
            'tax_total' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'in:'.implode(',', [
                Sale::PAYMENT_CASH,
                Sale::PAYMENT_CARD,
                Sale::PAYMENT_MIXED,
                Sale::PAYMENT_OTHER,
            ])],
        ]);

        $cartLines = $this->cartLinesFromPayload($validated['cart_payload']);

        try {
            $sale = $this->saleHoldService->hold($request->user(), $cartLines, [
                'patient_visit_record_id' => $validated['patient_visit_record_id'] ?? $validated['patient_visit_id'] ?? null,
                'discount_total' => $validated['discount_total'] ?? 0,
                'tax_total' => $validated['tax_total'] ?? 0,
                'payment_method' => $validated['payment_method'] ?? Sale::PAYMENT_CASH,
            ], filled($validated['held_sale_id'] ?? null) ? Sale::findOrFail($validated['held_sale_id']) : null);
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors(['hold' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('sales.pos')
            ->with('status', 'Sale '.$sale->sale_number.' held.');
    }

    public function resume(Sale $sale): View
    {
        abort_unless($sale->status === Sale::STATUS_HELD, 404);

        return view('sales.pos', [
            'heldSale' => $sale->load(['lines.product.saleUnits', 'lines.productUnit']),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function cartLinesFromPayload(string $cartPayload): array
    {
        $cartLines = json_decode($cartPayload, true);

        if (! is_array($cartLines)) {
            throw new InvalidArgumentException('Sale cart is invalid.');
        }

        return $cartLines;
    }
}
