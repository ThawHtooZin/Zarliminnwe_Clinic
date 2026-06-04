<?php

namespace App\Http\Controllers\Catalog;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Catalog\Services\ProductDeletionService;
use App\Domain\Shared\Exceptions\DeletionBlockException;
use App\Domain\Units\Services\UnitRelationshipService;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ProductController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly UnitRelationshipService $unitRelationshipService,
        private readonly ProductDeletionService $deletionService,
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
                $unitByInputIndex = $this->syncUnits($product, $validated['units']);
                $product->update([
                    'reorder_product_unit_id' => $this->resolveReorderProductUnitId($validated['reorder_unit_index'], $unitByInputIndex),
                ]);
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
                $unitByInputIndex = $this->syncUnits($product, $validated['units']);
                $product->update([
                    'reorder_product_unit_id' => $this->resolveReorderProductUnitId($validated['reorder_unit_index'], $unitByInputIndex),
                ]);
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

    public function destroy(Product $product): RedirectResponse
    {
        try {
            $oldValues = $product->load('units')->toArray();
            $this->deletionService->delete($product);
            $this->auditLogger->log('product.deleted', $product, $oldValues, null);
        } catch (DeletionBlockException $exception) {
            return redirect()->route('products.index')->with('error', $exception->getMessage());
        }

        return redirect()->route('products.index')->with('status', 'Product deleted.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $productId = $product?->id;

        $validated = $request->validate([
            'product_category_id' => ['required', 'exists:product_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($productId)],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'track_batch' => ['nullable', 'boolean'],
            'track_expiry' => ['nullable', 'boolean'],
            'reorder_unit_index' => ['nullable', 'integer', 'min:0', 'required_with:reorder_quantity'],
            'reorder_quantity' => ['nullable', 'numeric', 'gt:0', 'required_with:reorder_unit_index'],
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
            ->map(fn (array $unit, int $index): array => ['_form_index' => $index] + $unit)
            ->filter(fn (array $unit): bool => filled($unit['name'] ?? null))
            ->values()
            ->all();

        if ($units === []) {
            throw ValidationException::withMessages([
                'units' => 'At least one product unit is required.',
            ]);
        }

        $reorderUnitIndex = filled($validated['reorder_unit_index'] ?? null)
            ? (int) $validated['reorder_unit_index']
            : null;

        if ($reorderUnitIndex !== null && ! collect($units)->contains('_form_index', $reorderUnitIndex)) {
            throw ValidationException::withMessages([
                'reorder_unit_index' => 'The selected reorder unit must belong to this product.',
            ]);
        }

        return [
            'product' => [
                'product_category_id' => $validated['product_category_id'],
                'name' => $validated['name'],
                'sku' => $this->resolveSku($validated['sku'] ?? null, $validated['name'], $productId),
                'generic_name' => $validated['generic_name'] ?? null,
                'manufacturer' => $validated['manufacturer'] ?? null,
                'description' => $validated['description'] ?? null,
                'track_batch' => (bool) ($validated['track_batch'] ?? false),
                'track_expiry' => (bool) ($validated['track_expiry'] ?? false),
                'reorder_quantity' => $validated['reorder_quantity'] ?? null,
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ],
            'units' => $units,
            'reorder_unit_index' => $reorderUnitIndex,
        ];
    }

    private function resolveSku(?string $requestedSku, string $productName, ?int $productId = null): string
    {
        $requestedSku = trim((string) $requestedSku);

        if ($requestedSku !== '') {
            return $requestedSku;
        }

        $namePrefix = Str::upper(Str::substr(preg_replace('/[^A-Za-z0-9]/', '', $productName) ?: 'PRD', 0, 4));

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $candidate = $namePrefix.'-'.Str::upper(Str::random(4));

            $exists = Product::query()
                ->where('sku', $candidate)
                ->when($productId, fn ($query) => $query->where('id', '!=', $productId))
                ->exists();

            if (! $exists) {
                return $candidate;
            }
        }

        return 'PRD-'.Str::upper(Str::random(6));
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
     * @return array<int, ProductUnit>
     */
    private function syncUnits(Product $product, array $units): array
    {
        $existingIds = $product->units()->pluck('id')->all();
        $seenIds = [];
        $indexToUnit = [];

        foreach ($units as $unitData) {
            $formIndex = (int) $unitData['_form_index'];
            $unit = ProductUnit::updateOrCreate([
                'id' => $unitData['id'] ?? null,
                'product_id' => $product->id,
            ], [
                'name' => $unitData['name'],
                'abbreviation' => $unitData['abbreviation'],
                'level' => $unitData['level'] ?? $formIndex + 1,
                'conversion_factor' => $unitData['conversion_factor'] ?? null,
                'is_purchase_unit' => (bool) ($unitData['is_purchase_unit'] ?? false),
                'is_sale_unit' => (bool) ($unitData['is_sale_unit'] ?? false),
                'barcode' => filled($unitData['barcode'] ?? null) ? $unitData['barcode'] : null,
                'sale_price' => $unitData['sale_price'] ?? 0,
            ]);

            $seenIds[] = $unit->id;
            $indexToUnit[$formIndex] = $unit;
        }

        foreach ($units as $unitData) {
            $formIndex = (int) $unitData['_form_index'];
            $parentIndex = $unitData['parent_index'] ?? null;
            $indexToUnit[$formIndex]->update([
                'parent_product_unit_id' => $parentIndex !== null && isset($indexToUnit[$parentIndex])
                    ? $indexToUnit[$parentIndex]->id
                    : null,
            ]);
        }

        ProductUnit::whereIn('id', array_diff($existingIds, $seenIds))->delete();
        $this->unitRelationshipService->validateProductUnits($product->units()->get());

        return $indexToUnit;
    }

    /**
     * @param  array<int, ProductUnit>  $unitByInputIndex
     */
    private function resolveReorderProductUnitId(?int $reorderUnitIndex, array $unitByInputIndex): ?int
    {
        if ($reorderUnitIndex === null) {
            return null;
        }

        return $unitByInputIndex[$reorderUnitIndex]?->id ?? null;
    }
}
