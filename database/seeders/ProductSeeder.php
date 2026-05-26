<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'category' => 'Medicines',
                'sku' => 'PARA-500',
                'name' => 'Paracetamol 500mg',
                'generic_name' => 'Paracetamol',
                'manufacturer' => 'Sample Pharma',
            ],
            [
                'category' => 'Antibiotics',
                'sku' => 'AMOX-500',
                'name' => 'Amoxicillin 500mg',
                'generic_name' => 'Amoxicillin',
                'manufacturer' => 'Apex Pharma',
            ],
            [
                'category' => 'Medicines',
                'sku' => 'CET-10',
                'name' => 'Cetirizine 10mg',
                'generic_name' => 'Cetirizine',
                'manufacturer' => 'CareWell',
            ],
            [
                'category' => 'Medicines',
                'sku' => 'OME-20',
                'name' => 'Omeprazole 20mg',
                'generic_name' => 'Omeprazole',
                'manufacturer' => 'MediCore',
            ],
            [
                'category' => 'Medicines',
                'sku' => 'ORS-SACHET',
                'name' => 'ORS Sachet',
                'generic_name' => 'Oral Rehydration Salts',
                'manufacturer' => 'HydraLife',
            ],
            [
                'category' => 'Syrups',
                'sku' => 'COUGH-100',
                'name' => 'Cough Syrup 100ml',
                'generic_name' => 'Cough Mixture',
                'manufacturer' => 'SyrupCare',
            ],
            [
                'category' => 'Injections',
                'sku' => 'NS-500',
                'name' => 'Normal Saline 500ml',
                'generic_name' => 'Sodium Chloride 0.9%',
                'manufacturer' => 'IV Life',
            ],
            [
                'category' => 'Consumables',
                'sku' => 'SYR-5ML',
                'name' => 'Syringe 5ml',
                'generic_name' => 'Disposable Syringe',
                'manufacturer' => 'SafeMed',
                'track_batch' => false,
                'track_expiry' => false,
            ],
            [
                'category' => 'Consumables',
                'sku' => 'GLOVE-MED',
                'name' => 'Disposable Gloves Medium',
                'generic_name' => 'Medical Gloves',
                'manufacturer' => 'CleanHand',
                'track_batch' => false,
                'track_expiry' => false,
            ],
            [
                'category' => 'Topicals',
                'sku' => 'BETA-100',
                'name' => 'Betadine Solution 100ml',
                'generic_name' => 'Povidone Iodine',
                'manufacturer' => 'SkinSafe',
            ],
        ];

        foreach ($products as $product) {
            $category = ProductCategory::where('name', $product['category'])->firstOrFail();

            Product::updateOrCreate([
                'sku' => $product['sku'],
            ], [
                'product_category_id' => $category->id,
                'name' => $product['name'],
                'generic_name' => $product['generic_name'],
                'manufacturer' => $product['manufacturer'],
                'track_batch' => $product['track_batch'] ?? true,
                'track_expiry' => $product['track_expiry'] ?? true,
                'is_active' => true,
            ]);
        }
    }
}
