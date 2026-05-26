<?php

namespace App\Http\Controllers\Catalog;

use App\Domain\Audit\Services\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductCategoryController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(): View
    {
        $categories = ProductCategory::query()
            ->withCount('products')
            ->latest()
            ->paginate(15);

        return view('catalog.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('catalog.categories.form', [
            'category' => new ProductCategory(['is_active' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $category = ProductCategory::create($this->validated($request));
        $this->auditLogger->log('product_category.created', $category, null, $category->toArray());

        return redirect()->route('product-categories.index')->with('status', 'Category created.');
    }

    public function edit(ProductCategory $productCategory): View
    {
        return view('catalog.categories.form', ['category' => $productCategory]);
    }

    public function update(Request $request, ProductCategory $productCategory): RedirectResponse
    {
        $oldValues = $productCategory->toArray();
        $productCategory->update($this->validated($request));
        $this->auditLogger->log('product_category.updated', $productCategory, $oldValues, $productCategory->fresh()->toArray());

        return redirect()->route('product-categories.index')->with('status', 'Category updated.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => false];
    }
}
