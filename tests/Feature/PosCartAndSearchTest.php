<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\StockBalance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosCartAndSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    private Product $product;

    private ProductUnit $box;

    private ProductUnit $strip;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->cashier = User::factory()->create([
            'role' => User::ROLE_CASHIER,
        ]);

        $category = ProductCategory::create([
            'name' => 'Medicines',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'product_category_id' => $category->id,
            'name' => 'Paracetamol 500mg',
            'sku' => 'PARA-500',
            'generic_name' => 'Paracetamol',
            'manufacturer' => 'Sample Pharma',
            'image_path' => 'product-images/paracetamol.jpg',
            'is_active' => true,
        ]);

        $this->box = ProductUnit::create([
            'product_id' => $this->product->id,
            'name' => 'Box',
            'abbreviation' => 'box',
            'level' => 1,
            'is_purchase_unit' => true,
            'is_sale_unit' => true,
            'sale_price' => 25000,
            'barcode' => 'BOX-PARA-500',
        ]);

        $this->strip = ProductUnit::create([
            'product_id' => $this->product->id,
            'parent_product_unit_id' => $this->box->id,
            'name' => 'Strip',
            'abbreviation' => 'strip',
            'level' => 2,
            'conversion_factor' => 10,
            'is_purchase_unit' => true,
            'is_sale_unit' => true,
            'sale_price' => 2800,
            'barcode' => 'STRIP-PARA-500',
        ]);

        StockBalance::create([
            'product_id' => $this->product->id,
            'product_unit_id' => $this->box->id,
            'quantity' => 3,
        ]);
    }

    public function test_cashier_can_open_pos_with_optional_empty_patient_selector(): void
    {
        $this->actingAs($this->cashier)
            ->get(route('sales.pos'))
            ->assertOk()
            ->assertSee('Point of Sale')
            ->assertSee('Search by name, SKU, generic name, or barcode')
            ->assertSee('Patient Information (Optional)')
            ->assertSee('No patient selected')
            ->assertSee('Complete Sale')
            ->assertSee('Hold Sale')
            ->assertDontSee('Diagnosis')
            ->assertDontSee('Prescription')
            ->assertDontSee('Appointments');
    }

    public function test_product_search_returns_sale_units_prices_and_formatted_stock(): void
    {
        $this->actingAs($this->cashier)
            ->getJson(route('sales.products.search', ['search' => 'PARA']))
            ->assertOk()
            ->assertJsonPath('data.0.id', $this->product->id)
            ->assertJsonPath('data.0.name', 'Paracetamol 500mg')
            ->assertJsonPath('data.0.sku', 'PARA-500')
            ->assertJsonPath('data.0.image_url', url('storage/product-images/paracetamol.jpg'))
            ->assertJsonPath('data.0.initial', 'P')
            ->assertJsonPath('data.0.formatted_stock', '3 box')
            ->assertJsonPath('data.0.units.0.id', $this->box->id)
            ->assertJsonPath('data.0.units.0.sale_price', 25000)
            ->assertJsonPath('data.0.units.1.id', $this->strip->id)
            ->assertJsonPath('data.0.units.1.sale_price', 2800);
    }

    public function test_product_search_can_match_unit_barcode(): void
    {
        $this->actingAs($this->cashier)
            ->getJson(route('sales.products.search', ['search' => 'STRIP-PARA-500']))
            ->assertOk()
            ->assertJsonPath('data.0.id', $this->product->id);
    }

    public function test_stock_manager_cannot_open_pos(): void
    {
        $stockManager = User::factory()->create([
            'role' => User::ROLE_STOCK_MANAGER,
        ]);

        $this->actingAs($stockManager)
            ->get(route('sales.pos'))
            ->assertForbidden();
    }
}
