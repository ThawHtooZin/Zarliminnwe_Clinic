<?php

return [
    'datasets' => [
        'catalog' => [
            'label' => 'Product Catalog',
            'tables' => ['product_categories', 'products', 'product_units'],
        ],
        'suppliers' => [
            'label' => 'Suppliers',
            'tables' => ['suppliers'],
        ],
        'finance_categories' => [
            'label' => 'Finance Categories',
            'tables' => ['income_categories', 'expense_categories'],
        ],
        'patients' => [
            'label' => 'Patients & Visits',
            'tables' => ['patients', 'patient_visit_records', 'patient_diagnoses'],
        ],
        'finance_entries' => [
            'label' => 'Income & Expenses',
            'tables' => ['income_entries', 'expense_entries'],
        ],
        'pharmacy_sales' => [
            'label' => 'Pharmacy Sales (POS)',
            'tables' => ['sales', 'sale_lines', 'sale_line_stock_allocations'],
        ],
        'inventory' => [
            'label' => 'Inventory & Stock',
            'tables' => [
                'purchase_receipts',
                'purchase_receipt_lines',
                'stock_ledger',
                'stock_batches',
                'stock_balances',
                'stock_counts',
                'stock_count_lines',
            ],
        ],
        'administration' => [
            'label' => 'Users & Access',
            'tables' => ['roles', 'permissions', 'role_permission', 'users'],
            'column_exclude' => [
                'users' => ['password', 'remember_token'],
            ],
        ],
    ],

    'full_database_exclude' => [
        'migrations',
        'cache',
        'cache_locks',
        'sessions',
        'jobs',
        'job_batches',
        'failed_jobs',
        'password_reset_tokens',
    ],

    'restore_confirmation_phrase' => 'RESTORE DATABASE',

    'allow_database_restore' => env('APP_ALLOW_DB_RESTORE', true),
];
