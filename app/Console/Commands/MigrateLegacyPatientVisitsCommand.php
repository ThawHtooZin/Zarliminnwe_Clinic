<?php

namespace App\Console\Commands;

use App\Domain\Patients\Services\PatientCodeGenerator;
use App\Models\Patient;
use App\Models\PatientVisitRecord;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateLegacyPatientVisitsCommand extends Command
{
    protected $signature = 'patients:migrate-legacy-visits';

    protected $description = 'Migrate legacy patient_visits rows into patients and patient_visit_records';

    public function handle(PatientCodeGenerator $patientCodeGenerator): int
    {
        if (! Schema::hasTable('patient_visits')) {
            $this->info('Legacy patient_visits table not found. Nothing to migrate.');

            return self::SUCCESS;
        }

        if (PatientVisitRecord::query()->exists() && DB::table('patient_visits')->count() === 0) {
            $this->info('Legacy data already migrated.');

            return self::SUCCESS;
        }

        $legacyVisits = DB::table('patient_visits')->orderBy('id')->get();

        if ($legacyVisits->isEmpty()) {
            $this->info('No legacy patient visits to migrate.');

            return self::SUCCESS;
        }

        $migrated = 0;

        DB::transaction(function () use ($legacyVisits, $patientCodeGenerator, &$migrated): void {
            foreach ($legacyVisits as $legacyVisit) {
                $patient = Patient::create([
                    'patient_code' => $patientCodeGenerator->generate(),
                    'name' => $legacyVisit->patient_name,
                    'age' => (int) $legacyVisit->age,
                    'address' => '-',
                    'created_by' => $legacyVisit->created_by,
                    'created_at' => $legacyVisit->created_at,
                    'updated_at' => $legacyVisit->updated_at,
                ]);

                $record = PatientVisitRecord::create([
                    'patient_id' => $patient->id,
                    'visited_at' => $legacyVisit->visited_at,
                    'status' => PatientVisitRecord::STATUS_CLOSED,
                    'created_by' => $legacyVisit->created_by,
                    'created_at' => $legacyVisit->created_at,
                    'updated_at' => $legacyVisit->updated_at,
                ]);

                if (Schema::hasColumn('income_entries', 'patient_visit_id')) {
                    DB::table('income_entries')
                        ->where('patient_visit_id', $legacyVisit->id)
                        ->update(['patient_visit_record_id' => $record->id]);
                }

                if (Schema::hasColumn('sales', 'patient_visit_id')) {
                    DB::table('sales')
                        ->where('patient_visit_id', $legacyVisit->id)
                        ->update(['patient_visit_record_id' => $record->id]);
                }

                $migrated++;
            }
        });

        $this->info("Migrated {$migrated} legacy patient visit(s).");

        return self::SUCCESS;
    }
}
