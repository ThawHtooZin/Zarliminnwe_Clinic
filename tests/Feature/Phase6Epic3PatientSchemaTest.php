<?php

namespace Tests\Feature;

use App\Domain\Patients\Services\PatientCodeGenerator;
use App\Models\Patient;
use App\Models\PatientDiagnosis;
use App\Models\PatientVisitRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Phase6Epic3PatientSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_code_generator_produces_daily_sequential_codes(): void
    {
        $generator = app(PatientCodeGenerator::class);
        $date = Carbon::parse('2026-05-27');

        $first = $generator->generate($date);
        Patient::factory()->create(['patient_code' => $first, 'name' => 'First']);

        $second = $generator->generate($date);

        $this->assertSame('PAT-20260527-0001', $first);
        $this->assertSame('PAT-20260527-0002', $second);
    }

    public function test_patient_visit_record_supports_stackable_diagnoses(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CASHIER]);
        $record = PatientVisitRecord::factory()->create(['created_by' => $user->id]);

        PatientDiagnosis::create([
            'patient_visit_record_id' => $record->id,
            'diagnosis_text' => 'Hypertension',
            'recorded_at' => now(),
            'recorded_by' => $user->id,
        ]);
        PatientDiagnosis::create([
            'patient_visit_record_id' => $record->id,
            'diagnosis_text' => 'Type 2 diabetes',
            'recorded_at' => now(),
            'recorded_by' => $user->id,
        ]);

        $this->assertCount(2, $record->fresh()->diagnoses);
    }

    public function test_legacy_patient_visits_migration_command_maps_rows(): void
    {
        if (! Schema::hasTable('patient_visits')) {
            Schema::create('patient_visits', function (Blueprint $table): void {
                $table->id();
                $table->string('patient_name');
                $table->unsignedTinyInteger('age');
                $table->timestamp('visited_at');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        $user = User::factory()->create(['role' => User::ROLE_CASHIER]);

        \Illuminate\Support\Facades\DB::table('patient_visits')->insert([
            'patient_name' => 'Legacy Patient',
            'age' => 40,
            'visited_at' => '2026-05-20 08:00:00',
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Artisan::call('patients:migrate-legacy-visits');

        $this->assertDatabaseHas('patients', [
            'name' => 'Legacy Patient',
            'age' => 40,
            'address' => '-',
        ]);
        $this->assertDatabaseHas('patient_visit_records', [
            'patient_id' => Patient::query()->where('name', 'Legacy Patient')->value('id'),
        ]);
    }
}
