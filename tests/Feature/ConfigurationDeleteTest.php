<?php

namespace Tests\Feature;

use App\Models\ExpenseCategory;
use App\Models\ExpenseEntry;
use App\Models\IncomeCategory;
use App\Models\IncomeEntry;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\PurchaseReceipt;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfigurationDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_cashier_cannot_delete_product(): void
    {
        $cashier = User::factory()->create(['role' => User::ROLE_CASHIER]);
        $product = $this->createProduct();

        $this->actingAs($cashier)
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('dashboard'));

        $this->assertModelExists($product);
    }

    public function test_admin_can_delete_unused_supplier(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $supplier = Supplier::query()->create([
            'name' => 'Temp Supplier',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('suppliers.destroy', $supplier))
            ->assertRedirect(route('suppliers.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    public function test_supplier_delete_blocked_when_purchase_receipt_exists(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $supplier = Supplier::query()->create(['name' => 'Used Supplier', 'is_active' => true]);

        PurchaseReceipt::query()->create([
            'supplier_id' => $supplier->id,
            'receipt_number' => 'PR-TEST-001',
            'received_at' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $this->actingAs($admin)
            ->delete(route('suppliers.destroy', $supplier))
            ->assertRedirect(route('suppliers.index'))
            ->assertSessionHas('error');

        $this->assertModelExists($supplier);
    }

    public function test_income_category_delete_blocked_when_entries_exist(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $category = IncomeCategory::query()->create([
            'name' => 'Consultation',
            'type' => IncomeCategory::TYPE_SERVICE,
            'is_active' => true,
        ]);

        IncomeEntry::query()->create([
            'income_category_id' => $category->id,
            'amount' => 1000,
            'payment_method' => 'cash',
            'received_at' => now(),
        ]);

        $this->actingAs($admin)
            ->delete(route('finance.income-categories.destroy', $category))
            ->assertRedirect(route('finance.income-categories.index'))
            ->assertSessionHas('error');

        $this->assertModelExists($category);
    }

    public function test_admin_can_delete_product_without_operational_history(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $product = $this->createProduct();

        $this->actingAs($admin)
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('product_units', ['product_id' => $product->id]);
    }

    public function test_product_delete_blocked_when_sale_line_exists(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $product = $this->createProduct();

        \Illuminate\Support\Facades\DB::table('sales')->insert([
            'sale_number' => 'S-TEST-1',
            'status' => 'completed',
            'subtotal' => 10,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 10,
            'amount_paid' => 10,
            'change_amount' => 0,
            'payment_method' => 'cash',
            'sold_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $saleId = (int) \Illuminate\Support\Facades\DB::table('sales')->value('id');

        \Illuminate\Support\Facades\DB::table('sale_lines')->insert([
            'sale_id' => $saleId,
            'product_id' => $product->id,
            'product_unit_id' => $product->units()->first()->id,
            'quantity' => 1,
            'unit_price' => 10,
            'line_total' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('error');

        $this->assertModelExists($product);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error');

        $this->assertModelExists($admin);
    }

    private function createProduct(): Product
    {
        $category = ProductCategory::query()->create([
            'name' => 'Test Category',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'product_category_id' => $category->id,
            'name' => 'Test Product',
            'sku' => 'TEST-'.uniqid(),
            'is_active' => true,
        ]);

        ProductUnit::query()->create([
            'product_id' => $product->id,
            'name' => 'Box',
            'abbreviation' => 'box',
            'level' => 1,
            'is_purchase_unit' => true,
            'is_sale_unit' => true,
            'sale_price' => 10,
        ]);

        return $product->fresh();
    }
}
