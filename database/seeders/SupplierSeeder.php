<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            ['Default Medicine Supplier', '09 000 000 000', 'supplier@example.test', 'Yangon'],
            ['Apex Pharma Distribution', '09 111 222 333', 'sales@apex-pharma.test', 'Mandalay'],
            ['CareWell Medical Supplies', '09 222 333 444', 'orders@carewell.test', 'Yangon'],
            ['IV Life Wholesale', '09 333 444 555', 'contact@ivlife.test', 'Naypyidaw'],
            ['CleanHand Consumables', '09 444 555 666', 'hello@cleanhand.test', 'Yangon'],
        ];

        foreach ($suppliers as [$name, $phone, $email, $address]) {
            Supplier::updateOrCreate([
                'name' => $name,
            ], [
                'phone' => $phone,
                'email' => $email,
                'address' => $address,
                'is_active' => true,
            ]);
        }
    }
}
