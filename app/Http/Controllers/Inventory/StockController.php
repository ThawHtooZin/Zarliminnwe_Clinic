<?php

namespace App\Http\Controllers\Inventory;

use App\Domain\Units\Services\UnitRelationshipService;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockLedger;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockController extends Controller
{
    public function __construct(private readonly UnitRelationshipService $unitRelationshipService) {}

    public function index(Request $request): View
    {
        $products = Product::query()
            ->with(['stockBalances.productUnit', 'stockBalances.stockBatch'])
            ->when($request->string('search')->toString(), function ($query, string $search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(15)
            ->through(function (Product $product): Product {
                $product->formatted_stock = $this->unitRelationshipService->formatStock($product, $product->stockBalances);

                return $product;
            })
            ->withQueryString();

        return view('inventory.stock.index', compact('products'));
    }

    public function ledger(): View
    {
        $ledgers = StockLedger::with(['product', 'productUnit', 'stockBatch', 'creator'])
            ->latest()
            ->paginate(20);

        return view('inventory.stock.ledger', compact('ledgers'));
    }
}
