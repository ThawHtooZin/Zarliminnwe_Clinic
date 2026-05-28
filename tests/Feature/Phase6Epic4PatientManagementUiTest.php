<?php

namespace Tests\Feature;

use App\Models\IncomeCategory;
use App\Models\IncomeEntry;
use App\Models\Patient;
use App\Models\PatientDiagnosis;
use App\Models\PatientVisitRecord;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\IncomeCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase6Epic4PatientManagementUiTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->cashier = User::factory()->create(['role' => User::ROLE_CASHIER]);
        $this->seed(IncomeCategorySeeder::class);
    }

    public function test_cashier_can_search_patients_by_code_and_name(): void
    {
        $match = Patient::factory()->create([
            'patient_code' => 'PAT-20260527-0009',
            'name' => 'Daw Hnin',
        ]);
        Patient::factory()->create([
            'patient_code' => 'PAT-20260527-0010',
            'name' => 'U Aung',
        ]);

        $this->actingAs($this->cashier)
            ->get(route('patients.index', ['patient_code' => '0009', 'name' => 'Hnin']))
            ->assertOk()
            ->assertSee($match->patient_code)
            ->assertSee($match->name)
            ->assertDontSee('U Aung');
    }

    public function test_cashier_can_create_patient_and_patient_scoped_visit_record(): void
    {
        $this->actingAs($this->cashier)
            ->post(route('patients.store'), [
                'name' => 'U Kyi',
                'age' => 50,
                'address' => 'Yangon',
            ])
            ->assertRedirect();

        $patient = Patient::query()->firstOrFail();
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'patient.created',
            'auditable_type' => Patient::class,
            'auditable_id' => $patient->id,
        ]);

        $this->actingAs($this->cashier)
            ->post(route('patients.visit-records.store', $patient), [
                'visited_at' => '2026-05-27 11:00:00',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('patient_visit_records', [
            'patient_id' => $patient->id,
        ]);
    }

    public function test_visit_detail_supports_add_and_edit_diagnosis_in_chronological_order(): void
    {
        $visit = PatientVisitRecord::factory()->create(['created_by' => $this->cashier->id]);

        $this->actingAs($this->cashier)
            ->post(route('patient-visits.diagnoses.store', $visit), [
                'diagnosis_text' => 'Second diagnosis',
            ])
            ->assertRedirect(route('patient-visits.show', $visit));

        $first = PatientDiagnosis::create([
            'patient_visit_record_id' => $visit->id,
            'diagnosis_text' => 'First diagnosis',
            'recorded_at' => now()->subMinute(),
            'recorded_by' => $this->cashier->id,
        ]);

        $second = PatientDiagnosis::query()
            ->where('patient_visit_record_id', $visit->id)
            ->where('diagnosis_text', 'Second diagnosis')
            ->firstOrFail();

        $this->actingAs($this->cashier)
            ->put(route('patient-visits.diagnoses.update', [$visit, $second]), [
                'diagnosis_text' => 'Second diagnosis updated',
            ])
            ->assertRedirect(route('patient-visits.show', $visit));

        $this->actingAs($this->cashier)
            ->get(route('patient-visits.show', $visit))
            ->assertOk()
            ->assertSeeInOrder([$first->diagnosis_text, 'Second diagnosis updated']);
    }

    public function test_visit_detail_shows_linked_income_entries(): void
    {
        $visit = PatientVisitRecord::factory()->create(['created_by' => $this->cashier->id]);
        $category = IncomeCategory::query()->where('name', 'Consultation Fee')->firstOrFail();

        IncomeEntry::create([
            'income_category_id' => $category->id,
            'patient_visit_record_id' => $visit->id,
            'amount' => 5000,
            'payment_method' => IncomeEntry::PAYMENT_CASH,
            'received_at' => '2026-05-27 12:00:00',
            'received_by' => $this->cashier->id,
        ]);

        $this->actingAs($this->cashier)
            ->get(route('patient-visits.show', $visit))
            ->assertOk()
            ->assertSee('Visit Income')
            ->assertSee('Consultation Fee')
            ->assertSee('5,000.00');
    }

    public function test_visit_detail_shows_linked_pharmacy_sale(): void
    {
        $visit = PatientVisitRecord::factory()->create(['created_by' => $this->cashier->id]);

        Sale::create([
            'sale_number' => 'S-VISIT-001',
            'patient_visit_record_id' => $visit->id,
            'status' => Sale::STATUS_COMPLETED,
            'subtotal' => 3200,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 3200,
            'amount_paid' => 3200,
            'change_amount' => 0,
            'payment_method' => Sale::PAYMENT_CASH,
            'sold_by' => $this->cashier->id,
            'sold_at' => '2026-05-27 14:00:00',
        ]);

        $this->actingAs($this->cashier)
            ->get(route('patient-visits.show', $visit))
            ->assertOk()
            ->assertSee('Pharmacy Sale')
            ->assertSee('S-VISIT-001')
            ->assertSee('3,200.00')
            ->assertSee('View Sale');
    }
}

