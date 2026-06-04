<?php

namespace Tests\Feature;

use App\Domain\Finance\Services\UnifiedIncomeQueryService;
use App\Models\AuditLog;
use App\Models\IncomeCategory;
use App\Models\IncomeEntry;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\IncomeCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ListExcelExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_cannot_export_products_list(): void
    {
        $cashier = User::factory()->create(['role' => User::ROLE_CASHIER]);

        $this->actingAs($cashier)
            ->get(route('products.export'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_pharmacist_products_export_returns_xlsx_with_headers_and_rows(): void
    {
        $pharmacist = User::factory()->create(['role' => User::ROLE_PHARMACIST]);
        $category = ProductCategory::query()->create(['name' => 'Tablets', 'is_active' => true]);

        Product::query()->create([
            'product_category_id' => $category->id,
            'name' => 'Exportable Tablet',
            'sku' => 'EXP-001',
            'generic_name' => 'Generic A',
            'is_active' => true,
        ]);

        Product::query()->create([
            'product_category_id' => $category->id,
            'name' => 'Hidden SKU Item',
            'sku' => 'ZZZ-999',
            'is_active' => true,
        ]);

        $content = $this->actingAs($pharmacist)
            ->get(route('products.export', ['search' => 'EXP-']))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->streamedContent();

        $sheet = $this->loadFirstSheet($content);

        $this->assertSame('Product', $sheet->getCell('A1')->getValue());
        $this->assertSame('SKU', $sheet->getCell('B1')->getValue());
        $this->assertSame('Category', $sheet->getCell('C1')->getValue());
        $this->assertSame('Units', $sheet->getCell('D1')->getValue());
        $this->assertSame('Status', $sheet->getCell('E1')->getValue());
        $this->assertSame(2, $sheet->getHighestRow());
        $this->assertStringContainsString('Exportable Tablet', (string) $sheet->getCell('A2')->getValue());
        $this->assertSame('EXP-001', $sheet->getCell('B2')->getValue());

        $this->assertTrue(
            AuditLog::query()->where('action', 'list_export.generated')->exists(),
        );
    }

    public function test_income_export_respects_pharmacy_sale_filter(): void
    {
        $cashier = User::factory()->create(['role' => User::ROLE_CASHIER]);
        $this->seed(IncomeCategorySeeder::class);

        $category = IncomeCategory::where('name', 'Consultation Fee')->firstOrFail();

        IncomeEntry::create([
            'income_category_id' => $category->id,
            'amount' => 1500,
            'payment_method' => IncomeEntry::PAYMENT_CASH,
            'received_at' => '2026-05-26 09:00:00',
            'received_by' => $cashier->id,
        ]);

        Sale::create([
            'sale_number' => 'S-EXPORT-001',
            'status' => Sale::STATUS_COMPLETED,
            'subtotal' => 3200,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 3200,
            'amount_paid' => 3200,
            'change_amount' => 0,
            'payment_method' => Sale::PAYMENT_CASH,
            'sold_by' => $cashier->id,
            'sold_at' => '2026-05-26 10:00:00',
        ]);

        $content = $this->actingAs($cashier)
            ->get(route('finance.income.export', [
                'received_from' => '2026-05-26',
                'received_to' => '2026-05-26',
                'income_category_id' => UnifiedIncomeQueryService::PHARMACY_SALE_FILTER,
            ]))
            ->assertOk()
            ->streamedContent();

        $sheet = $this->loadFirstSheet($content);

        $this->assertSame('Date', $sheet->getCell('A1')->getValue());
        $this->assertSame('Source', $sheet->getCell('B1')->getValue());
        $this->assertSame(2, $sheet->getHighestRow());
        $this->assertSame('Pharmacy Sale', $sheet->getCell('B2')->getValue());
    }

    private function loadFirstSheet(string $content): \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
    {
        $path = tempnam(sys_get_temp_dir(), 'list-export-');
        file_put_contents($path, $content);

        return IOFactory::load($path)->getActiveSheet();
    }
}
