<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Shared\Exceptions\DeletionBlockException;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\DB;

class ProductCategoryDeletionService
{
    public function __construct(
        private readonly ProductDeletionService $productDeletionService,
    ) {}

    /**
     * @throws DeletionBlockException
     */
    public function delete(ProductCategory $category): void
    {
        DB::transaction(function () use ($category): void {
            $products = $category->products()->get();

            foreach ($products as $product) {
                $this->productDeletionService->delete($product);
            }

            $category->delete();
        });
    }
}
