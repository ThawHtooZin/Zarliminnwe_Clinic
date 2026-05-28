<?php

namespace App\Http\Controllers\Inventory;

use App\Domain\Inventory\Services\StockCountService;
use App\Http\Controllers\Controller;
use App\Models\StockBalance;
use App\Models\StockCount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class StockCountController extends Controller
{
    public function __construct(private readonly StockCountService $stockCountService) {}

    public function index(): View
    {
        $stockCounts = StockCount::with(['countedBy', 'reviewedBy'])
            ->withCount('lines')
            ->latest()
            ->paginate(15);

        return view('inventory.stock-counts.index', compact('stockCounts'));
    }

    public function create(): View
    {
        return view('inventory.stock-counts.create', [
            'balances' => $this->availableBalances(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
            'stock_balance_ids' => ['required', 'array', 'min:1'],
            'stock_balance_ids.*' => ['integer', 'exists:stock_balances,id'],
        ]);

        try {
            $stockCount = $this->stockCountService->createDraftFromBalances(
                $validated['stock_balance_ids'],
                $request->user(),
                $validated['notes'] ?? null
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['stock_balance_ids' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('stock-counts.show', $stockCount)->with('status', 'Stock count draft created.');
    }

    public function show(StockCount $stockCount): View
    {
        $stockCount->load([
            'countedBy',
            'reviewedBy',
            'lines.product',
            'lines.productUnit',
            'lines.stockBatch',
            'lines.adjustmentLedger',
        ]);

        return view('inventory.stock-counts.show', compact('stockCount'));
    }

    public function update(Request $request, StockCount $stockCount): RedirectResponse
    {
        $validated = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.id' => ['required', 'integer', 'exists:stock_count_lines,id'],
            'lines.*.counted_quantity' => ['required', 'numeric', 'min:0'],
            'lines.*.notes' => ['nullable', 'string'],
        ]);

        try {
            $this->stockCountService->updateDraftLines($stockCount, $validated['lines']);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['lines' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('stock-counts.show', $stockCount)->with('status', 'Stock count saved.');
    }

    private function availableBalances()
    {
        return StockBalance::with(['product.category', 'productUnit', 'stockBatch'])
            ->whereHas('product', fn ($query) => $query->where('is_active', true))
            ->orderBy('product_id')
            ->orderBy('product_unit_id')
            ->orderBy('stock_batch_id')
            ->get();
    }
}
