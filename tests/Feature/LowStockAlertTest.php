<?php

namespace Tests\Feature;

use App\Domain\Inventory\Services\LowStockAlertService;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\StockBalance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LowStockAlertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_product_form_saves_reorder_unit_and_quantity(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_STOCK_MANAGER]);
        $category = ProductCategory::create(['name' => 'Medicines', 'is_active' => true]);

        $this->actingAs($user)
            ->post(route('products.store'), $this->productPayload($category, [
                'reorder_unit_index' => 1,
                'reorder_quantity' => 20,
            ]))
            ->assertRedirect(route('products.index'));

        $product = Product::with('reorderUnit')->where('sku', 'PARA-500')->firstOrFail();

        $this->assertSame(20.0, (float) $product->reorder_quantity);
        $this->assertSame('Strip', $product->reorderUnit->name);
    }

    public function test_product_form_rejects_invalid_reorder_unit_row(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_STOCK_MANAGER]);
        $category = ProductCategory::create(['name' => 'Medicines', 'is_active' => true]);

        $this->actingAs($user)
            ->from(route('products.create'))
            ->post(route('products.store'), $this->productPayload($category, [
                'reorder_unit_index' => 5,
                'reorder_quantity' => 20,
            ]))
            ->assertRedirect(route('products.create'))
            ->assertSessionHasErrors('reorder_unit_index');
    }

    public function test_product_form_requires_positive_reorder_quantity_when_reorder_unit_is_selected(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_STOCK_MANAGER]);
        $category = ProductCategory::create(['name' => 'Medicines', 'is_active' => true]);

        $this->actingAs($user)
            ->from(route('products.create'))
            ->post(route('products.store'), $this->productPayload($category, [
                'reorder_unit_index' => 0,
                'reorder_quantity' => 0,
            ]))
            ->assertRedirect(route('products.create'))
            ->assertSessionHasErrors('reorder_quantity');
    }

    public function test_low_stock_service_detects_same_unit_shortage(): void
    {
        [, , $strip] = $this->createProductWithUnits(reorderQuantity: 20);

        StockBalance::create([
            'product_id' => $strip->product_id,
            'product_unit_id' => $strip->id,
            'quantity' => 12,
        ]);

        $alerts = app(LowStockAlertService::class)->getLowStockProducts();

        $this->assertCount(1, $alerts);
        $this->assertSame(12.0, $alerts->first()['available_quantity']);
        $this->assertSame(8.0, $alerts->first()['shortage_quantity']);
        $this->assertSame('12 strip', $alerts->first()['formatted_available_stock']);
    }

    public function test_low_stock_service_detects_related_unit_shortage(): void
    {
        [, $box, $strip] = $this->createProductWithUnits(reorderQuantity: 20);

        StockBalance::create([
            'product_id' => $strip->product_id,
            'product_unit_id' => $box->id,
            'quantity' => 1,
        ]);
        StockBalance::create([
            'product_id' => $strip->product_id,
            'product_unit_id' => $strip->id,
            'quantity' => 5,
        ]);

        $alert = app(LowStockAlertService::class)->getLowStockProducts()->first();

        $this->assertNotNull($alert);
        $this->assertSame(15.0, $alert['available_quantity']);
        $this->assertSame(5.0, $alert['shortage_quantity']);
        $this->assertSame('15 strip', $alert['formatted_available_stock']);
    }

    public function test_low_stock_service_hides_products_above_threshold(): void
    {
        [, $box, $strip] = $this->createProductWithUnits(reorderQuantity: 20);

        StockBalance::create([
            'product_id' => $strip->product_id,
            'product_unit_id' => $box->id,
            'quantity' => 2,
        ]);
        StockBalance::create([
            'product_id' => $strip->product_id,
            'product_unit_id' => $strip->id,
            'quantity' => 5,
        ]);

        $this->assertCount(0, app(LowStockAlertService::class)->getLowStockProducts());
    }

    public function test_authorized_user_can_view_low_stock_page_and_filter_by_category(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_PHARMACIST]);
        [$medicineProduct, , $medicineStrip, $medicineCategory] = $this->createProductWithUnits(reorderQuantity: 20);
        [$supplyProduct, , $supplyStrip] = $this->createProductWithUnits(
            sku: 'AMOX-500',
            name: 'Amoxicillin 500mg',
            categoryName: 'Antibiotics',
            reorderQuantity: 30,
        );

        StockBalance::create([
            'product_id' => $medicineProduct->id,
            'product_unit_id' => $medicineStrip->id,
            'quantity' => 10,
        ]);
        StockBalance::create([
            'product_id' => $supplyProduct->id,
            'product_unit_id' => $supplyStrip->id,
            'quantity' => 10,
        ]);

        $this->actingAs($user)
            ->get(route('stock-control.low-stock', ['category_id' => $medicineCategory->id]))
            ->assertOk()
            ->assertSee('Low-Stock Alerts')
            ->assertSee('Paracetamol 500mg')
            ->assertSee('10 strip')
            ->assertDontSee('Amoxicillin 500mg');
    }

    public function test_cashier_cannot_view_low_stock_page(): void
    {
        $cashier = User::factory()->create(['role' => User::ROLE_CASHIER]);

        $this->actingAs($cashier)
            ->get(route('stock-control.low-stock'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'You do not have access to this page.');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function productPayload(ProductCategory $category, array $overrides = []): array
    {
        return array_replace_recursive([
            'product_category_id' => $category->id,
            'name' => 'Paracetamol 500mg',
            'sku' => 'PARA-500',
            'generic_name' => 'Paracetamol',
            'manufacturer' => 'Sample Pharma',
            'track_batch' => 1,
            'track_expiry' => 1,
            'is_active' => 1,
            'units' => [
                [
                    'name' => 'Box',
                    'abbreviation' => 'box',
                    'level' => 1,
                    'is_purchase_unit' => 1,
                    'is_sale_unit' => 1,
                    'sale_price' => 25000,
                ],
                [
                    'name' => 'Strip',
                    'abbreviation' => 'strip',
                    'level' => 2,
                    'parent_index' => 0,
                    'conversion_factor' => 10,
                    'is_purchase_unit' => 1,
                    'is_sale_unit' => 1,
                    'sale_price' => 2800,
                ],
            ],
        ], $overrides);
    }

    /**
     * @return array{Product, ProductUnit, ProductUnit, ProductCategory}
     */
    private function createProductWithUnits(
        string $sku = 'PARA-500',
        string $name = 'Paracetamol 500mg',
        string $categoryName = 'Medicines',
        float $reorderQuantity = 20
    ): array {
        $category = ProductCategory::create([
            'name' => $categoryName,
            'is_active' => true,
        ]);

        $product = Product::create([
            'product_category_id' => $category->id,
            'name' => $name,
            'sku' => $sku,
            'generic_name' => $name,
            'is_active' => true,
            'reorder_quantity' => $reorderQuantity,
        ]);

        $box = ProductUnit::create([
            'product_id' => $product->id,
            'name' => 'Box',
            'abbreviation' => 'box',
            'level' => 1,
            'is_purchase_unit' => true,
            'is_sale_unit' => true,
            'sale_price' => 25000,
        ]);

        $strip = ProductUnit::create([
            'product_id' => $product->id,
            'parent_product_unit_id' => $box->id,
            'name' => 'Strip',
            'abbreviation' => 'strip',
            'level' => 2,
            'conversion_factor' => 10,
            'is_purchase_unit' => true,
            'is_sale_unit' => true,
            'sale_price' => 2800,
        ]);

        $product->update(['reorder_product_unit_id' => $strip->id]);

        return [$product, $box, $strip, $category];
    }
}
