<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\PatientVisitRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatientVisitRecord>
 */
class PatientVisitRecordFactory extends Factory
{
    protected $model = PatientVisitRecord::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'visited_at' => now(),
            'status' => PatientVisitRecord::STATUS_OPEN,
            'created_by' => null,
        ];
    }
}
