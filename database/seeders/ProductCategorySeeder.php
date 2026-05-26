<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Medicines' => 'General pharmacy medicines.',
            'Antibiotics' => 'Antibiotic medicines.',
            'Syrups' => 'Liquid oral medicines.',
            'Injections' => 'Injectable and IV items.',
            'Topicals' => 'External use medicines and solutions.',
            'Consumables' => 'Disposable clinic consumables.',
            'Clinic Supplies' => 'Non-medicine clinic supplies.',
        ];

        foreach ($categories as $name => $description) {
            ProductCategory::updateOrCreate([
                'name' => $name,
            ], [
                'description' => $description,
                'is_active' => true,
            ]);
        }
    }
}
