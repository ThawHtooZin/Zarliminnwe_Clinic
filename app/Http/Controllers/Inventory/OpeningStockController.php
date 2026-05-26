<?php

namespace App\Http\Controllers\Inventory;

use App\Domain\Inventory\Services\StockPostingService;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OpeningStockController extends Controller
{
    public function __construct(private readonly StockPostingService $stockPostingService) {}

    public function create(): View
    {
        return view('inventory.opening-stock.create', [
            'products' => Product::with('units')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'product_unit_id' => ['required', 'exists:product_units,id'],
            'quantity' => ['required', 'numeric', 'min:0.000001'],
            'batch_number' => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date'],
            'reason' => ['nullable', 'string'],
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $unit = ProductUnit::findOrFail($validated['product_unit_id']);

        if ($unit->product_id !== $product->id) {
            return back()
                ->withErrors(['product_unit_id' => 'The selected unit does not belong to the selected product.'])
                ->withInput();
        }

        $this->stockPostingService->postOpeningStock($product, $unit, $validated['quantity'], [
            'batch_number' => $validated['batch_number'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'received_at' => now()->toDateString(),
        ], $validated['reason'] ?? null);

        return redirect()->route('stock.index')->with('status', 'Opening stock posted.');
    }
}
