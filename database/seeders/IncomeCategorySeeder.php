<?php

namespace Database\Seeders;

use App\Models\IncomeCategory;
use Illuminate\Database\Seeder;

class IncomeCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Consultation Fee',
                'type' => IncomeCategory::TYPE_SERVICE,
                'description' => 'Patient consultation service fee.',
            ],
            [
                'name' => 'Service Fee',
                'type' => IncomeCategory::TYPE_SERVICE,
                'description' => 'General clinic service fee.',
            ],
            [
                'name' => 'Other Income',
                'type' => IncomeCategory::TYPE_GENERAL,
                'description' => 'Non-patient general income.',
            ],
        ];

        foreach ($categories as $category) {
            IncomeCategory::updateOrCreate(
                ['name' => $category['name']],
                [
                    'type' => $category['type'],
                    'description' => $category['description'],
                    'is_active' => true,
                ]
            );
        }
    }
}
