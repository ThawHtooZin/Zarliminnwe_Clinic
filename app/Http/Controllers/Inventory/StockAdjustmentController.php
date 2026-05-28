<?php

namespace App\Http\Controllers\Inventory;

use App\Domain\Inventory\Services\StockAdjustmentService;
use App\Http\Controllers\Controller;
use App\Models\StockBalance;
use App\Models\StockLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class StockAdjustmentController extends Controller
{
    public function __construct(private readonly StockAdjustmentService $stockAdjustmentService) {}

    public function create(Request $request): View
    {
        $balance = StockBalance::with(['product', 'productUnit', 'stockBatch'])
            ->findOrFail($request->integer('stock_balance_id'));

        return view('inventory.adjustments.create', compact('balance'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'stock_balance_id' => ['required', 'integer', 'exists:stock_balances,id'],
            'quantity' => ['required', 'numeric', 'min:0.000001'],
            'reason' => ['required', 'string'],
        ]);

        $balance = StockBalance::with(['product', 'productUnit', 'stockBatch'])->findOrFail($validated['stock_balance_id']);

        if ((float) $validated['quantity'] > (float) $balance->quantity) {
            return back()->withErrors(['quantity' => 'Quantity cannot be greater than remaining stock.'])->withInput();
        }

        try {
            $this->stockAdjustmentService->postManualAdjustment(
                product: $balance->product,
                unit: $balance->productUnit,
                quantity: $validated['quantity'],
                direction: StockLedger::DIRECTION_OUT,
                reason: $validated['reason'],
                stockBatch: $balance->stockBatch
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['adjustment' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('stock-control.expiry')->with('status', 'Expired stock adjustment posted.');
    }
}
