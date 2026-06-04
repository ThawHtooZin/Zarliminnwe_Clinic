<?php

return [
    'products' => [
        'filename' => 'products',
        'permission_route' => 'products.export',
        'headers' => ['Product', 'SKU', 'Category', 'Units', 'Status'],
    ],
    'product-categories' => [
        'filename' => 'product-categories',
        'permission_route' => 'product-categories.export',
        'headers' => ['Name', 'Products', 'Status'],
    ],
    'suppliers' => [
        'filename' => 'suppliers',
        'permission_route' => 'suppliers.export',
        'headers' => ['Name', 'Phone', 'Email', 'Status'],
    ],
    'finance.income-categories' => [
        'filename' => 'income-categories',
        'permission_route' => 'finance.income-categories.export',
        'headers' => ['Name', 'Type', 'Status'],
    ],
    'finance.expense-categories' => [
        'filename' => 'expense-categories',
        'permission_route' => 'finance.expense-categories.export',
        'headers' => ['Name', 'Status'],
    ],
    'finance.income' => [
        'filename' => 'income',
        'permission_route' => 'finance.income.export',
        'headers' => ['Date', 'Source', 'Category', 'Patient Visit', 'Amount', 'Payment', 'Recorded By'],
    ],
    'finance.expenses' => [
        'filename' => 'expenses',
        'permission_route' => 'finance.expenses.export',
        'headers' => ['Date', 'Category', 'Amount', 'Payee', 'Payment', 'Created By'],
    ],
];
