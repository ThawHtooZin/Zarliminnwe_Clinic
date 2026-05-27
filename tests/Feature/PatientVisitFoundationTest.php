<?php

namespace Tests\Feature;

use App\Models\PatientVisit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PatientVisitFoundationTest extends TestCase
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
    }

    public function test_patient_visits_table_has_only_ultra_minimal_business_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('patient_visits', [
            'id',
            'patient_name',
            'age',
            'visited_at',
            'created_by',
            'created_at',
            'updated_at',
        ]));

        foreach ($this->forbiddenColumns() as $column) {
            $this->assertFalse(Schema::hasColumn('patient_visits', $column), $column.' must not exist.');
        }
    }

    public function test_patient_visit_model_uses_only_allowed_fillable_fields(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $patientVisit = PatientVisit::create([
            'patient_name' => 'Daw Mya',
            'age' => 52,
            'visited_at' => '2026-05-26 09:30:00',
            'created_by' => $user->id,
        ]);

        $this->assertTrue($patientVisit->createdBy->is($user));
        $this->assertTrue(method_exists($patientVisit, 'incomeEntries'));
        $this->assertTrue($patientVisit->isFillable('patient_name'));
        $this->assertTrue($patientVisit->isFillable('age'));
        $this->assertTrue($patientVisit->isFillable('visited_at'));
        $this->assertFalse($patientVisit->isFillable('diagnosis'));
        $this->assertFalse($patientVisit->isFillable('appointment_at'));
    }

    public function test_authorized_user_can_create_patient_visit_and_action_is_audited(): void
    {
        $this->actingAs($this->cashier)
            ->post(route('patient-visits.store'), $this->validPayload())
            ->assertRedirect();

        $patientVisit = PatientVisit::firstOrFail();

        $this->assertSame('U Aung', $patientVisit->patient_name);
        $this->assertSame(45, $patientVisit->age);
        $this->assertSame($this->cashier->id, $patientVisit->created_by);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->cashier->id,
            'action' => 'patient_visit.created',
            'auditable_type' => PatientVisit::class,
            'auditable_id' => $patientVisit->id,
        ]);
    }

    public function test_authorized_user_can_update_allowed_patient_visit_fields(): void
    {
        $patientVisit = PatientVisit::create($this->validPayload() + [
            'created_by' => $this->cashier->id,
        ]);

        $this->actingAs($this->cashier)
            ->put(route('patient-visits.update', $patientVisit), [
                'patient_name' => 'Daw Hla',
                'age' => 46,
                'visited_at' => '2026-05-27 10:15:00',
            ])
            ->assertRedirect(route('patient-visits.show', $patientVisit));

        $patientVisit->refresh();

        $this->assertSame('Daw Hla', $patientVisit->patient_name);
        $this->assertSame(46, $patientVisit->age);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->cashier->id,
            'action' => 'patient_visit.updated',
            'auditable_type' => PatientVisit::class,
            'auditable_id' => $patientVisit->id,
        ]);
    }

    public function test_patient_visit_validation_rejects_clinical_fields(): void
    {
        $this->actingAs($this->cashier)
            ->from(route('patient-visits.create'))
            ->post(route('patient-visits.store'), $this->validPayload() + [
                'diagnosis' => 'Fever',
                'vitals' => 'High temperature',
                'prescription' => 'Medication note',
                'clinical_notes' => 'Clinical note',
                'medical_history' => 'Past illness',
            ])
            ->assertRedirect(route('patient-visits.create'))
            ->assertSessionHasErrors([
                'diagnosis',
                'vitals',
                'prescription',
                'clinical_notes',
                'medical_history',
            ]);

        $this->assertDatabaseCount('patient_visits', 0);
    }

    public function test_patient_visit_validation_rejects_appointment_fields(): void
    {
        $this->actingAs($this->cashier)
            ->from(route('patient-visits.create'))
            ->post(route('patient-visits.store'), $this->validPayload() + [
                'doctor_id' => 1,
                'appointment_at' => '2026-05-27 09:00:00',
                'appointment_status' => 'booked',
                'queue_number' => 'Q-001',
            ])
            ->assertRedirect(route('patient-visits.create'))
            ->assertSessionHasErrors([
                'doctor_id',
                'appointment_at',
                'appointment_status',
                'queue_number',
            ]);

        $this->assertDatabaseCount('patient_visits', 0);
    }

    public function test_patient_visit_form_does_not_render_ehr_or_appointment_fields(): void
    {
        $this->actingAs($this->cashier)
            ->get(route('patient-visits.create'))
            ->assertOk()
            ->assertSee('Patient Name')
            ->assertSee('Age')
            ->assertSee('Visit Time')
            ->assertDontSee('Diagnosis')
            ->assertDontSee('Prescription')
            ->assertDontSee('Vitals')
            ->assertDontSee('Appointment')
            ->assertDontSee('Doctor')
            ->assertDontSee('Queue');
    }

    public function test_patient_visit_list_filters_by_name_and_visit_date(): void
    {
        PatientVisit::create([
            'patient_name' => 'U Aung',
            'age' => 45,
            'visited_at' => '2026-05-26 09:30:00',
            'created_by' => $this->cashier->id,
        ]);
        PatientVisit::create([
            'patient_name' => 'Daw Mya',
            'age' => 52,
            'visited_at' => '2026-05-20 09:30:00',
            'created_by' => $this->cashier->id,
        ]);

        $this->actingAs($this->cashier)
            ->get(route('patient-visits.index', [
                'patient_name' => 'Aung',
                'visited_from' => '2026-05-26',
                'visited_to' => '2026-05-26',
            ]))
            ->assertOk()
            ->assertSee('U Aung')
            ->assertDontSee('Daw Mya');
    }

    public function test_guest_and_stock_manager_cannot_access_patient_visits(): void
    {
        $this->get(route('patient-visits.index'))->assertRedirect(route('login'));

        $stockManager = User::factory()->create(['role' => User::ROLE_STOCK_MANAGER]);

        $this->actingAs($stockManager)
            ->get(route('patient-visits.index'))
            ->assertForbidden();
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'patient_name' => 'U Aung',
            'age' => 45,
            'visited_at' => '2026-05-26 09:30:00',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function forbiddenColumns(): array
    {
        return [
            'diagnosis',
            'symptoms',
            'vitals',
            'prescription',
            'clinical_notes',
            'medical_history',
            'doctor_id',
            'appointment_at',
            'appointment_status',
            'queue_number',
        ];
    }
}
