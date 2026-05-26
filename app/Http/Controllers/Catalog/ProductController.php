<?php

namespace App\Http\Controllers\Catalog;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Units\Services\UnitRelationshipService;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class ProductController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly UnitRelationshipService $unitRelationshipService
    ) {}

    public function index(Request $request): View
    {
        $products = Product::query()
            ->with(['category', 'units'])
            ->when($request->string('search')->toString(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('generic_name', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('catalog.products.index', compact('products'));
    }

    public function create(): View
    {
        return view('catalog.products.form', [
            'product' => new Product(['is_active' => true]),
            'categories' => ProductCategory::where('is_active', true)->orderBy('name')->get(),
            'units' => collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $imagePath = $this->storeProductImage($request);

        if ($imagePath) {
            $validated['product']['image_path'] = $imagePath;
        }

        try {
            DB::transaction(function () use ($validated, &$product): void {
                $product = Product::create($validated['product']);
                $this->syncUnits($product, $validated['units']);
                $this->auditLogger->log('product.created', $product, null, $product->load('units')->toArray());
            });
        } catch (Throwable $throwable) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            throw $throwable;
        }

        return redirect()->route('products.index')->with('status', 'Product created.');
    }

    public function show(Product $product): View
    {
        $product->load(['category', 'units.parent', 'stockBalances.productUnit', 'stockBalances.stockBatch']);

        return view('catalog.products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        return view('catalog.products.form', [
            'product' => $product->load('units'),
            'categories' => ProductCategory::where('is_active', true)->orderBy('name')->get(),
            'units' => $product->units,
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $this->validated($request, $product);
        $oldImagePath = $product->image_path;
        $newImagePath = $this->storeProductImage($request);

        if ($newImagePath) {
            $validated['product']['image_path'] = $newImagePath;
        }

        try {
            DB::transaction(function () use ($product, $validated): void {
                $oldValues = $product->load('units')->toArray();
                $product->update($validated['product']);
                $this->syncUnits($product, $validated['units']);
                $this->auditLogger->log('product.updated', $product, $oldValues, $product->fresh()->load('units')->toArray());
            });
        } catch (Throwable $throwable) {
            if ($newImagePath) {
                Storage::disk('public')->delete($newImagePath);
            }

            throw $throwable;
        }

        if ($newImagePath && $oldImagePath) {
            Storage::disk('public')->delete($oldImagePath);
        }

        return redirect()->route('products.index')->with('status', 'Product updated.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $productId = $product?->id;

        $validated = $request->validate([
            'product_category_id' => ['required', 'exists:product_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($productId)],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'track_batch' => ['nullable', 'boolean'],
            'track_expiry' => ['nullable', 'boolean'],
            'reorder_quantity' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'units' => ['required', 'array', 'min:1'],
            'units.*.id' => ['nullable', 'integer'],
            'units.*.name' => ['nullable', 'string', 'max:255'],
            'units.*.abbreviation' => ['nullable', 'string', 'max:50'],
            'units.*.level' => ['nullable', 'integer', 'min:1'],
            'units.*.parent_index' => ['nullable', 'integer', 'min:0'],
            'units.*.conversion_factor' => ['nullable', 'numeric', 'min:0.000001'],
            'units.*.is_purchase_unit' => ['nullable', 'boolean'],
            'units.*.is_sale_unit' => ['nullable', 'boolean'],
            'units.*.barcode' => ['nullable', 'string', 'max:255'],
            'units.*.sale_price' => ['required_with:units.*.name', 'numeric', 'min:0'],
        ]);

        $units = collect($validated['units'])
            ->filter(fn (array $unit): bool => filled($unit['name'] ?? null))
            ->values()
            ->all();

        if ($units === []) {
            abort(422, 'At least one product unit is required.');
        }

        return [
            'product' => [
                'product_category_id' => $validated['product_category_id'],
                'name' => $validated['name'],
                'sku' => $validated['sku'],
                'generic_name' => $validated['generic_name'] ?? null,
                'manufacturer' => $validated['manufacturer'] ?? null,
                'description' => $validated['description'] ?? null,
                'track_batch' => (bool) ($validated['track_batch'] ?? false),
                'track_expiry' => (bool) ($validated['track_expiry'] ?? false),
                'reorder_quantity' => $validated['reorder_quantity'] ?? null,
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ],
            'units' => $units,
        ];
    }

    private function storeProductImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        return $request->file('image')->store('product-images', 'public');
    }

    /**
     * @param  array<int, array<string, mixed>>  $units
     */
    private function syncUnits(Product $product, array $units): void
    {
        $existingIds = $product->units()->pluck('id')->all();
        $seenIds = [];
        $indexToUnit = [];

        foreach ($units as $index => $unitData) {
            $unit = ProductUnit::updateOrCreate([
                'id' => $unitData['id'] ?? null,
                'product_id' => $product->id,
            ], [
                'name' => $unitData['name'],
                'abbreviation' => $unitData['abbreviation'],
                'level' => $unitData['level'] ?? $index + 1,
                'conversion_factor' => $unitData['conversion_factor'] ?? null,
                'is_purchase_unit' => (bool) ($unitData['is_purchase_unit'] ?? false),
                'is_sale_unit' => (bool) ($unitData['is_sale_unit'] ?? false),
                'barcode' => filled($unitData['barcode'] ?? null) ? $unitData['barcode'] : null,
                'sale_price' => $unitData['sale_price'] ?? 0,
            ]);

            $seenIds[] = $unit->id;
            $indexToUnit[$index] = $unit;
        }

        foreach ($units as $index => $unitData) {
            $parentIndex = $unitData['parent_index'] ?? null;
            $indexToUnit[$index]->update([
                'parent_product_unit_id' => $parentIndex !== null && isset($indexToUnit[$parentIndex])
                    ? $indexToUnit[$parentIndex]->id
                    : null,
            ]);
        }

        ProductUnit::whereIn('id', array_diff($existingIds, $seenIds))->delete();
        $this->unitRelationshipService->validateProductUnits($product->units()->get());
    }
}
