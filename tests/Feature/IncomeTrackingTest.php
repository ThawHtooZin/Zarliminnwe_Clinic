<?php

namespace Tests\Feature;

use App\Models\IncomeCategory;
use App\Models\IncomeEntry;
use App\Models\PatientVisitRecord;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\StockBalance;
use App\Models\User;
use Database\Seeders\IncomeCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IncomeTrackingTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->cashier = User::factory()->create([
            'role' => User::ROLE_CASHIER,
        ]);

        $this->seed(IncomeCategorySeeder::class);
    }

    public function test_income_entries_table_has_required_columns_and_no_sale_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('income_entries', [
            'id',
            'income_category_id',
            'patient_visit_record_id',
            'amount',
            'payment_method',
            'received_at',
            'received_by',
            'description',
            'created_at',
            'updated_at',
        ]));

        $this->assertFalse(Schema::hasColumn('income_entries', 'sale_id'));
        $this->assertFalse(Schema::hasColumn('income_entries', 'sale_number'));
    }

    public function test_cashier_can_create_income_with_patient_visit_link(): void
    {
        $visit = $this->createPatientVisit();
        $category = IncomeCategory::where('name', 'Consultation Fee')->firstOrFail();

        $this->actingAs($this->cashier)
            ->post(route('finance.income.store'), $this->validPayload($category, $visit))
            ->assertRedirect(route('patient-visits.show', $visit));

        $entry = IncomeEntry::firstOrFail();

        $this->assertSame($visit->id, $entry->patient_visit_record_id);
        $this->assertSame(5000.0, (float) $entry->amount);
        $this->assertSame($this->cashier->id, $entry->received_by);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'income_entry.created',
            'auditable_type' => IncomeEntry::class,
            'auditable_id' => $entry->id,
        ]);
    }

    public function test_cashier_can_create_income_without_patient_visit(): void
    {
        $category = IncomeCategory::where('name', 'Other Income')->firstOrFail();

        $this->actingAs($this->cashier)
            ->post(route('finance.income.store'), $this->validPayload($category))
            ->assertRedirect(route('finance.income.index'));

        $entry = IncomeEntry::firstOrFail();

        $this->assertNull($entry->patient_visit_record_id);
    }

    public function test_income_create_rejects_pharmacy_sale_fields(): void
    {
        $category = IncomeCategory::where('name', 'Consultation Fee')->firstOrFail();

        $this->actingAs($this->cashier)
            ->from(route('finance.income.create'))
            ->post(route('finance.income.store'), $this->validPayload($category) + [
                'sale_id' => 1,
                'sale_number' => 'S-001',
                'grand_total' => 9999,
            ])
            ->assertRedirect(route('finance.income.create'))
            ->assertSessionHasErrors(['sale_id', 'sale_number', 'grand_total']);

        $this->assertDatabaseCount('income_entries', 0);
    }

    public function test_inactive_income_category_cannot_be_used(): void
    {
        $category = IncomeCategory::where('name', 'Consultation Fee')->firstOrFail();
        $category->update(['is_active' => false]);

        $this->actingAs($this->cashier)
            ->from(route('finance.income.create'))
            ->post(route('finance.income.store'), $this->validPayload($category))
            ->assertRedirect(route('finance.income.create'))
            ->assertSessionHasErrors('form');

        $this->assertDatabaseCount('income_entries', 0);
    }

    public function test_completing_pharmacy_sale_does_not_create_income_entry(): void
    {
        [$product, , $strip] = $this->createProductWithUnits();

        StockBalance::create([
            'product_id' => $product->id,
            'product_unit_id' => $strip->id,
            'quantity' => 10,
        ]);

        $this->actingAs($this->cashier)
            ->post(route('sales.store'), [
                'cart_payload' => json_encode([
                    [
                        'productId' => $product->id,
                        'unitId' => $strip->id,
                        'quantity' => 2,
                        'unitPrice' => 2800,
                    ],
                ]),
                'discount_total' => 0,
                'tax_total' => 0,
                'amount_paid' => 6000,
                'payment_method' => Sale::PAYMENT_CASH,
            ])
            ->assertRedirect(route('sales.pos'));

        $this->assertSame(1, Sale::count());
        $this->assertDatabaseCount('income_entries', 0);
    }

    public function test_income_create_form_prefills_patient_visit_from_query(): void
    {
        $visit = $this->createPatientVisit();

        $this->actingAs($this->cashier)
            ->get(route('finance.income.create', ['patient_visit_id' => $visit->id]))
            ->assertOk()
            ->assertSee('Record Income Entry')
            ->assertSee($visit->patient_name)
            ->assertDontSee('Diagnosis')
            ->assertDontSee('Prescription');
    }

    public function test_income_list_filters_by_date_and_category(): void
    {
        $category = IncomeCategory::where('name', 'Consultation Fee')->firstOrFail();
        $otherCategory = IncomeCategory::where('name', 'Other Income')->firstOrFail();

        IncomeEntry::create([
            'income_category_id' => $category->id,
            'amount' => 1000,
            'payment_method' => IncomeEntry::PAYMENT_CASH,
            'received_at' => '2026-05-26 10:00:00',
            'received_by' => $this->cashier->id,
        ]);
        IncomeEntry::create([
            'income_category_id' => $otherCategory->id,
            'amount' => 2000,
            'payment_method' => IncomeEntry::PAYMENT_CASH,
            'received_at' => '2026-05-20 10:00:00',
            'received_by' => $this->cashier->id,
        ]);

        $this->actingAs($this->cashier)
            ->get(route('finance.income.index', [
                'received_from' => '2026-05-26',
                'received_to' => '2026-05-26',
                'income_category_id' => $category->id,
            ]))
            ->assertOk()
            ->assertSee('1,000.00')
            ->assertDontSee('2,000.00');
    }

    public function test_income_index_lists_completed_pharmacy_sale_with_pseudo_category(): void
    {
        $visit = $this->createPatientVisit();
        $category = IncomeCategory::where('name', 'Consultation Fee')->firstOrFail();

        IncomeEntry::create([
            'income_category_id' => $category->id,
            'patient_visit_record_id' => $visit->id,
            'amount' => 1500,
            'payment_method' => IncomeEntry::PAYMENT_CASH,
            'received_at' => '2026-05-26 09:00:00',
            'received_by' => $this->cashier->id,
        ]);

        Sale::create([
            'sale_number' => 'S-INDEX-001',
            'patient_visit_record_id' => $visit->id,
            'status' => Sale::STATUS_COMPLETED,
            'subtotal' => 4200,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 4200,
            'amount_paid' => 4200,
            'change_amount' => 0,
            'payment_method' => Sale::PAYMENT_CASH,
            'sold_by' => $this->cashier->id,
            'sold_at' => '2026-05-26 10:00:00',
        ]);

        $this->actingAs($this->cashier)
            ->get(route('finance.income.index', [
                'received_from' => '2026-05-26',
                'received_to' => '2026-05-26',
            ]))
            ->assertOk()
            ->assertSee('Pharmacy Sale')
            ->assertSee('S-INDEX-001')
            ->assertSee('4,200.00')
            ->assertSee('Consultation Fee')
            ->assertSee('1,500.00');
    }

    public function test_guest_and_stock_manager_cannot_access_income_entries(): void
    {
        $this->get(route('finance.income.index'))->assertRedirect(route('login'));

        $stockManager = User::factory()->create(['role' => User::ROLE_STOCK_MANAGER]);

        $this->actingAs($stockManager)
            ->get(route('finance.income.index'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'You do not have access to this page.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(IncomeCategory $category, ?PatientVisitRecord $visit = null): array
    {
        return [
            'income_category_id' => $category->id,
            'patient_visit_id' => $visit?->id,
            'amount' => 5000,
            'payment_method' => IncomeEntry::PAYMENT_CASH,
            'received_at' => '2026-05-26 10:30:00',
            'description' => 'Consultation fee collected.',
        ];
    }

    private function createPatientVisit(): PatientVisitRecord
    {
        return PatientVisitRecord::factory()->create([
            'visited_at' => '2026-05-26 09:00:00',
            'created_by' => $this->cashier->id,
        ]);
    }

    /**
     * @return array{Product, ProductUnit, ProductUnit}
     */
    private function createProductWithUnits(): array
    {
        $category = ProductCategory::create([
            'name' => 'Medicines',
            'is_active' => true,
        ]);

        $product = Product::create([
            'product_category_id' => $category->id,
            'name' => 'Paracetamol 500mg',
            'sku' => 'PARA-500',
            'is_active' => true,
        ]);

        $box = ProductUnit::create([
            'product_id' => $product->id,
            'name' => 'Box',
            'abbreviation' => 'box',
            'level' => 2,
            'is_purchase_unit' => true,
            'is_sale_unit' => false,
            'sale_price' => 28000,
        ]);

        $strip = ProductUnit::create([
            'product_id' => $product->id,
            'name' => 'Strip',
            'abbreviation' => 'strip',
            'level' => 1,
            'parent_product_unit_id' => $box->id,
            'conversion_factor' => 10,
            'is_purchase_unit' => false,
            'is_sale_unit' => true,
            'sale_price' => 2800,
        ]);

        return [$product, $box, $strip];
    }
}
