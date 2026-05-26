<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_stock_manager_can_create_product_with_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'role' => User::ROLE_STOCK_MANAGER,
        ]);
        $category = ProductCategory::create([
            'name' => 'Medicines',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('products.store'), $this->validPayload($category, [
                'image' => UploadedFile::fake()->image('paracetamol.jpg'),
            ]))
            ->assertRedirect(route('products.index'));

        $product = Product::where('sku', 'PARA-IMG')->firstOrFail();

        $this->assertNotNull($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);

        $this->actingAs($user)
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('storage/'.$product->image_path, false);
    }

    public function test_new_product_image_replaces_existing_image_on_update(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'role' => User::ROLE_STOCK_MANAGER,
        ]);
        $category = ProductCategory::create([
            'name' => 'Medicines',
            'is_active' => true,
        ]);
        $oldImagePath = 'product-images/old.jpg';
        Storage::disk('public')->put($oldImagePath, 'old image');

        $product = Product::create([
            'product_category_id' => $category->id,
            'name' => 'Paracetamol 500mg',
            'sku' => 'PARA-IMG',
            'image_path' => $oldImagePath,
            'is_active' => true,
        ]);
        ProductUnit::create([
            'product_id' => $product->id,
            'name' => 'Box',
            'abbreviation' => 'box',
            'level' => 1,
            'is_purchase_unit' => true,
            'is_sale_unit' => true,
            'sale_price' => 25000,
        ]);

        $this->actingAs($user)
            ->post(route('products.update', $product), $this->validPayload($category, [
                '_method' => 'PUT',
                'name' => 'Paracetamol 500mg Updated',
                'image' => UploadedFile::fake()->image('paracetamol-new.jpg'),
                'units' => [
                    [
                        'id' => $product->units()->firstOrFail()->id,
                        'name' => 'Box',
                        'abbreviation' => 'box',
                        'level' => 1,
                        'is_purchase_unit' => 1,
                        'is_sale_unit' => 1,
                        'sale_price' => 25000,
                    ],
                ],
            ]))
            ->assertRedirect(route('products.index'));

        $product->refresh();

        $this->assertNotSame($oldImagePath, $product->image_path);
        Storage::disk('public')->assertMissing($oldImagePath);
        Storage::disk('public')->assertExists($product->image_path);

        $this->actingAs($user)
            ->get(route('products.edit', $product))
            ->assertOk()
            ->assertSee('storage/'.$product->image_path, false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(ProductCategory $category, array $overrides = []): array
    {
        return array_replace_recursive([
            'product_category_id' => $category->id,
            'name' => 'Paracetamol 500mg',
            'sku' => 'PARA-IMG',
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
            ],
        ], $overrides);
    }
}
