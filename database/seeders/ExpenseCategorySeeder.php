<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Rent' => 'Monthly rent expense.',
            'Salary' => 'Staff salary expense.',
            'Utilities' => 'Electricity, water, and utility bills.',
            'Supplies' => 'General supplies expense.',
            'Maintenance' => 'Repairs and maintenance expense.',
            'Other Expense' => 'Miscellaneous expense.',
        ];

        foreach ($categories as $name => $description) {
            ExpenseCategory::updateOrCreate(
                ['name' => $name],
                [
                    'description' => $description,
                    'is_active' => true,
                ]
            );
        }
    }
}
