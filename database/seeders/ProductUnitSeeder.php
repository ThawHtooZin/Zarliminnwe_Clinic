<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Database\Seeder;

class ProductUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedBoxStripSmallest('PARA-500', 'Pill', 'pill', 25000, 2800, 300);
        $this->seedBoxStripSmallest('AMOX-500', 'Capsule', 'cap', 42000, 4800, 550);
        $this->seedBoxStripSmallest('CET-10', 'Tablet', 'tab', 18000, 2200, 250);
        $this->seedBoxStripSmallest('OME-20', 'Capsule', 'cap', 36000, 4200, 500);
        $this->seedBoxChild('ORS-SACHET', 'Sachet', 'sachet', 15000, 400);
        $this->seedSingleUnit('COUGH-100', 'Bottle', 'btl', 4500);
        $this->seedSingleUnit('NS-500', 'Bottle', 'btl', 2500);
        $this->seedBoxChild('SYR-5ML', 'Piece', 'pc', 30000, 350);
        $this->seedBoxChild('GLOVE-MED', 'Pair', 'pair', 18000, 250);
        $this->seedSingleUnit('BETA-100', 'Bottle', 'btl', 5500);
    }

    private function seedBoxStripSmallest(
        string $sku,
        string $smallestName,
        string $smallestAbbreviation,
        float $boxPrice,
        float $stripPrice,
        float $smallestPrice
    ): void {
        $product = Product::where('sku', $sku)->firstOrFail();
        $prefix = str_replace('-', '', $sku);

        $box = $this->unit($product, 'Box', 'box', 1, null, null, true, true, $boxPrice, "{$prefix}-BOX");
        $strip = $this->unit($product, 'Strip', 'strip', 2, $box->id, 10, true, true, $stripPrice, "{$prefix}-STRIP");
        $this->unit($product, $smallestName, $smallestAbbreviation, 3, $strip->id, 10, false, true, $smallestPrice, "{$prefix}-".strtoupper($smallestAbbreviation));
    }

    private function seedBoxChild(string $sku, string $childName, string $childAbbreviation, float $boxPrice, float $childPrice): void
    {
        $product = Product::where('sku', $sku)->firstOrFail();
        $prefix = str_replace('-', '', $sku);

        $box = $this->unit($product, 'Box', 'box', 1, null, null, true, true, $boxPrice, "{$prefix}-BOX");
        $this->unit($product, $childName, $childAbbreviation, 2, $box->id, 50, false, true, $childPrice, "{$prefix}-".strtoupper($childAbbreviation));
    }

    private function seedSingleUnit(string $sku, string $unitName, string $abbreviation, float $salePrice): void
    {
        $product = Product::where('sku', $sku)->firstOrFail();
        $prefix = str_replace('-', '', $sku);

        $this->unit($product, $unitName, $abbreviation, 1, null, null, true, true, $salePrice, "{$prefix}-".strtoupper($abbreviation));
    }

    private function unit(
        Product $product,
        string $name,
        string $abbreviation,
        int $level,
        ?int $parentId,
        ?float $conversionFactor,
        bool $isPurchaseUnit,
        bool $isSaleUnit,
        float $salePrice,
        string $barcode
    ): ProductUnit {
        return ProductUnit::updateOrCreate([
            'product_id' => $product->id,
            'abbreviation' => $abbreviation,
        ], [
            'name' => $name,
            'level' => $level,
            'parent_product_unit_id' => $parentId,
            'conversion_factor' => $conversionFactor,
            'is_purchase_unit' => $isPurchaseUnit,
            'is_sale_unit' => $isSaleUnit,
            'sale_price' => $salePrice,
            'barcode' => $barcode,
        ]);
    }
}
