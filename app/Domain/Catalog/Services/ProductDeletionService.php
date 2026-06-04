<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Shared\Exceptions\DeletionBlockException;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductDeletionService
{
    /**
     * @throws DeletionBlockException
     */
    public function delete(Product $product): void
    {
        $this->assertDeletable($product);

        DB::transaction(function () use ($product): void {
            $imagePath = $product->image_path;

            $product->update(['reorder_product_unit_id' => null]);

            DB::table('stock_balances')->where('product_id', $product->id)->delete();
            DB::table('stock_batches')->where('product_id', $product->id)->delete();
            DB::table('stock_ledgers')->where('product_id', $product->id)->delete();

            $product->delete();

            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
        });
    }

    /**
     * @throws DeletionBlockException
     */
    public function assertDeletable(Product $product): void
    {
        $reasons = [];

        $this->addCount($reasons, 'sale line(s)', DB::table('sale_lines')->where('product_id', $product->id)->count());
        $this->addCount($reasons, 'stock movement(s)', DB::table('stock_ledgers')->where('product_id', $product->id)->count());
        $this->addCount($reasons, 'stock balance row(s)', DB::table('stock_balances')->where('product_id', $product->id)->count());
        $this->addCount($reasons, 'stock batch(es)', DB::table('stock_batches')->where('product_id', $product->id)->count());
        $this->addCount($reasons, 'purchase line(s)', DB::table('purchase_receipt_lines')->where('product_id', $product->id)->count());
        $this->addCount($reasons, 'stock count line(s)', DB::table('stock_count_lines')->where('product_id', $product->id)->count());

        if ($reasons !== []) {
            throw new DeletionBlockException($reasons);
        }
    }

    /**
     * @param  array<string, int>  $reasons
     */
    private function addCount(array &$reasons, string $label, int $count): void
    {
        if ($count > 0) {
            $reasons[$label] = $count;
        }
    }
}
