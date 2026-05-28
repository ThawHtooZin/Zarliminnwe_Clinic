<?php

namespace Tests\Feature;

use App\Domain\Finance\Data\UnifiedIncomeLine;
use App\Domain\Finance\Services\UnifiedIncomeQueryService;
use App\Models\IncomeCategory;
use App\Models\IncomeEntry;
use App\Models\PatientVisitRecord;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\IncomeCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnifiedIncomeQueryTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cashier = User::factory()->create([
            'role' => User::ROLE_CASHIER,
        ]);

        $this->seed(IncomeCategorySeeder::class);
    }

    public function test_merged_list_includes_income_entry_and_completed_sale(): void
    {
        $visit = PatientVisitRecord::factory()->create(['created_by' => $this->cashier->id]);
        $category = IncomeCategory::where('name', 'Consultation Fee')->firstOrFail();

        IncomeEntry::create([
            'income_category_id' => $category->id,
            'patient_visit_record_id' => $visit->id,
            'amount' => 3000,
            'payment_method' => IncomeEntry::PAYMENT_CASH,
            'received_at' => '2026-05-26 10:00:00',
            'received_by' => $this->cashier->id,
        ]);

        Sale::create([
            'sale_number' => 'S-20260526-0001',
            'patient_visit_record_id' => $visit->id,
            'status' => Sale::STATUS_COMPLETED,
            'subtotal' => 5600,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 5600,
            'amount_paid' => 6000,
            'change_amount' => 400,
            'payment_method' => Sale::PAYMENT_CASH,
            'sold_by' => $this->cashier->id,
            'sold_at' => '2026-05-26 11:00:00',
        ]);

        $lines = app(UnifiedIncomeQueryService::class)->forPatientVisit($visit);

        $this->assertCount(2, $lines);
        $this->assertTrue($lines->contains(fn (UnifiedIncomeLine $line): bool => $line->isPharmacySale() && $line->amount === 5600.0));
        $this->assertTrue($lines->contains(fn (UnifiedIncomeLine $line): bool => $line->isIncomeEntry() && $line->amount === 3000.0));
    }

    public function test_voided_sales_are_excluded_from_unified_income(): void
    {
        $visit = PatientVisitRecord::factory()->create(['created_by' => $this->cashier->id]);

        Sale::create([
            'sale_number' => 'S-VOID-001',
            'patient_visit_record_id' => $visit->id,
            'status' => Sale::STATUS_VOIDED,
            'subtotal' => 9999,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 9999,
            'amount_paid' => 9999,
            'change_amount' => 0,
            'payment_method' => Sale::PAYMENT_CASH,
            'sold_by' => $this->cashier->id,
            'sold_at' => '2026-05-26 12:00:00',
        ]);

        $lines = app(UnifiedIncomeQueryService::class)->forPatientVisit($visit);

        $this->assertCount(0, $lines);
    }

    public function test_pharmacy_sale_category_filter_returns_only_sales(): void
    {
        $category = IncomeCategory::where('name', 'Consultation Fee')->firstOrFail();

        IncomeEntry::create([
            'income_category_id' => $category->id,
            'amount' => 1000,
            'payment_method' => IncomeEntry::PAYMENT_CASH,
            'received_at' => '2026-05-26 10:00:00',
            'received_by' => $this->cashier->id,
        ]);

        Sale::create([
            'sale_number' => 'S-20260526-0002',
            'status' => Sale::STATUS_COMPLETED,
            'subtotal' => 2500,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 2500,
            'amount_paid' => 2500,
            'change_amount' => 0,
            'payment_method' => Sale::PAYMENT_CASH,
            'sold_by' => $this->cashier->id,
            'sold_at' => '2026-05-26 11:00:00',
        ]);

        $paginator = app(UnifiedIncomeQueryService::class)->paginatedForFilters([
            'received_from' => '2026-05-26',
            'received_to' => '2026-05-26',
            'income_category_id' => UnifiedIncomeQueryService::PHARMACY_SALE_FILTER,
        ], 15);

        $this->assertCount(1, $paginator->items());
        $this->assertTrue($paginator->items()[0]->isPharmacySale());
        $this->assertSame(UnifiedIncomeLine::CATEGORY_PHARMACY_SALE, $paginator->items()[0]->categoryLabel);
    }
}
