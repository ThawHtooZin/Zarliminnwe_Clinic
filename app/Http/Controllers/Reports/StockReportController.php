<?php

namespace App\Http\Controllers\Reports;

use App\Domain\Inventory\Services\InventoryReportService;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockBatch;
use App\Models\StockLedger;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockReportController extends Controller
{
    public function __construct(private readonly InventoryReportService $inventoryReportService) {}

    public function stockOnHand(Request $request): View
    {
        $filters = $this->filters($request);

        return view('reports.stock-on-hand', [
            'balances' => $this->inventoryReportService->stockOnHand($filters)->paginate(20)->withQueryString(),
            ...$this->metadata(),
            'filters' => $filters,
        ]);
    }

    public function stockMovements(Request $request): View
    {
        $filters = $this->filters($request);

        return view('reports.stock-movements', [
            'ledgers' => $this->inventoryReportService->stockMovements($filters)->paginate(20)->withQueryString(),
            ...$this->metadata(),
            'filters' => $filters,
            'types' => $this->ledgerTypes(),
            'directions' => $this->directions(),
        ]);
    }

    public function lowStock(Request $request): View
    {
        $filters = $this->filters($request);

        return view('reports.low-stock', [
            'alerts' => $this->inventoryReportService->lowStock($filters),
            ...$this->metadata(),
            'filters' => $filters,
        ]);
    }

    public function expiry(Request $request): View
    {
        $filters = $this->filters($request) + ['days' => max($request->integer('days', 90), 1)];

        return view('reports.expiry', [
            'alerts' => $this->inventoryReportService->expiry($filters),
            ...$this->metadata(),
            'filters' => $filters,
        ]);
    }

    public function adjustments(Request $request): View
    {
        $filters = $this->filters($request);

        return view('reports.stock-adjustments', [
            'ledgers' => $this->inventoryReportService->adjustments($filters)->paginate(20)->withQueryString(),
            ...$this->metadata(),
            'filters' => $filters,
            'directions' => $this->directions(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function metadata(): array
    {
        return [
            'products' => Product::orderBy('name')->get(),
            'categories' => ProductCategory::orderBy('name')->get(),
            'batches' => StockBatch::with('product')->orderBy('batch_number')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return [
            'search' => $request->string('search')->toString(),
            'product_id' => $request->integer('product_id') ?: null,
            'category_id' => $request->integer('category_id') ?: null,
            'batch_id' => $request->integer('batch_id') ?: null,
            'active_status' => $request->string('active_status')->toString(),
            'type' => $request->string('type')->toString(),
            'direction' => $request->string('direction')->toString(),
            'date_from' => $request->date('date_from')?->toDateString(),
            'date_to' => $request->date('date_to')?->toDateString(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function ledgerTypes(): array
    {
        return [
            StockLedger::TYPE_OPENING_STOCK,
            StockLedger::TYPE_PURCHASE_RECEIPT,
            StockLedger::TYPE_SALE,
            StockLedger::TYPE_SALE_VOID,
            StockLedger::TYPE_ADJUSTMENT,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function directions(): array
    {
        return [
            StockLedger::DIRECTION_IN,
            StockLedger::DIRECTION_OUT,
        ];
    }
}
