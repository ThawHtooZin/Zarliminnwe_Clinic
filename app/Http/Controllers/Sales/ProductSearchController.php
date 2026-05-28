<?php

namespace App\Http\Controllers\Sales;

use App\Domain\Sales\Services\PosStockAvailabilityService;
use App\Domain\Units\Services\UnitRelationshipService;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductSearchController extends Controller
{
    public function __construct(
        private readonly UnitRelationshipService $unitRelationshipService,
        private readonly PosStockAvailabilityService $posStockAvailabilityService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));

        $products = Product::query()
            ->with([
                'saleUnits' => fn ($query) => $query->orderBy('level'),
                'stockBalances.productUnit',
                'stockBalances.stockBatch',
            ])
            ->where('is_active', true)
            ->whereHas('saleUnits')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('generic_name', 'like', "%{$search}%")
                        ->orWhereHas('units', function ($query) use ($search): void {
                            $query->where('barcode', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('name')
            ->limit(24)
            ->get()
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'generic_name' => $product->generic_name,
                'image_url' => $product->image_path ? asset('storage/'.$product->image_path) : null,
                'initial' => strtoupper(substr($product->name, 0, 1)),
                'formatted_stock' => $this->unitRelationshipService->formatStock($product, $product->stockBalances),
                'units' => $product->saleUnits->map(function ($unit) use ($product): array {
                    $availability = $this->posStockAvailabilityService->availabilityForUnit($product->stockBalances, $unit);

                    return [
                        'id' => $unit->id,
                        'name' => $unit->name,
                        'abbreviation' => $unit->abbreviation,
                        'sale_price' => (float) $unit->sale_price,
                        'is_available' => $availability['is_available'],
                        'max_qty' => $availability['max_qty'],
                    ];
                })->values(),
            ]);

        return response()->json([
            'data' => $products,
        ]);
    }
}
