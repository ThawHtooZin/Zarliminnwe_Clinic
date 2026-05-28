<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\PatientVisitRecord;
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

    public function test_patient_module_tables_exist_with_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('patients', [
            'id',
            'patient_code',
            'name',
            'age',
            'address',
            'created_by',
            'created_at',
            'updated_at',
        ]));

        $this->assertTrue(Schema::hasColumns('patient_visit_records', [
            'id',
            'patient_id',
            'visited_at',
            'status',
            'created_by',
            'created_at',
            'updated_at',
        ]));

        $this->assertFalse(Schema::hasTable('patient_visits'));
    }

    public function test_visit_can_only_be_created_from_patient_profile(): void
    {
        $patient = Patient::factory()->create(['created_by' => $this->cashier->id]);

        $this->actingAs($this->cashier)
            ->post(route('patients.visit-records.store', $patient), [
                'visited_at' => '2026-05-26 09:30:00',
            ])
            ->assertRedirect();

        $record = PatientVisitRecord::query()->firstOrFail();

        $this->assertSame($patient->id, $record->patient_id);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->cashier->id,
            'action' => 'patient_visit_record.created',
            'auditable_type' => PatientVisitRecord::class,
            'auditable_id' => $record->id,
        ]);
    }

    public function test_standalone_patient_visit_routes_are_not_available(): void
    {
        $this->actingAs($this->cashier)
            ->get('/patient-visits/create')
            ->assertNotFound();

        $this->actingAs($this->cashier)
            ->get('/patient-visits')
            ->assertNotFound();
    }

    public function test_patient_scoped_visit_form_only_shows_visit_time(): void
    {
        $patient = Patient::factory()->create([
            'name' => 'U Aung',
            'age' => 45,
            'created_by' => $this->cashier->id,
        ]);

        $this->actingAs($this->cashier)
            ->get(route('patients.visit-records.create', $patient))
            ->assertOk()
            ->assertSee('U Aung')
            ->assertSee('Visit Time')
            ->assertDontSee('name="patient_name"', false)
            ->assertDontSee('name="age"', false);
    }

    public function test_authorized_user_can_update_visit_time_from_patient_profile(): void
    {
        $patient = Patient::factory()->create(['created_by' => $this->cashier->id]);
        $record = PatientVisitRecord::factory()->create([
            'patient_id' => $patient->id,
            'visited_at' => '2026-05-26 09:30:00',
            'created_by' => $this->cashier->id,
        ]);

        $this->actingAs($this->cashier)
            ->put(route('patients.visit-records.update', [$patient, $record]), [
                'visited_at' => '2026-05-27 10:15:00',
            ])
            ->assertRedirect(route('patient-visits.show', $record));

        $record->refresh();

        $this->assertSame('2026-05-27 10:15:00', $record->visited_at->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->cashier->id,
            'action' => 'patient_visit_record.updated',
            'auditable_type' => PatientVisitRecord::class,
            'auditable_id' => $record->id,
        ]);
    }

    public function test_guest_cannot_access_patient_visit_detail(): void
    {
        $visit = PatientVisitRecord::factory()->create(['created_by' => $this->cashier->id]);

        $this->get(route('patient-visits.show', $visit))->assertRedirect(route('login'));
    }
}
