<?php

namespace Database\Factories;

use App\Domain\Patients\Services\PatientCodeGenerator;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition(): array
    {
        return [
            'patient_code' => app(PatientCodeGenerator::class)->generate(),
            'name' => fake()->name(),
            'age' => fake()->numberBetween(1, 90),
            'address' => fake()->streetAddress(),
            'created_by' => null,
        ];
    }
}
