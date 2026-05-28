<?php

return [
    'groups' => [
        'main' => [
            'label' => 'Main Features',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'route' => 'dashboard',
                    'match' => 'dashboard',
                    'icon' => 'D',
                    'screen' => 'dashboard',
                ],
                [
                    'label' => 'POS',
                    'route' => 'sales.pos',
                    'match' => 'sales.pos',
                    'icon' => 'S',
                    'screen' => 'sales.pos',
                ],
                [
                    'label' => 'Patients',
                    'route' => 'patients.index',
                    'match' => 'patients.*|patient-visits.*',
                    'icon' => 'P',
                    'screen' => 'patients',
                ],
                [
                    'label' => 'Sales History',
                    'route' => 'sales.index',
                    'match' => 'sales.index',
                    'icon' => 'H',
                    'screen' => 'sales.index',
                ],
            ],
        ],
        'management' => [
            'label' => 'Management',
            'items' => [
                [
                    'label' => 'Products',
                    'route' => 'products.index',
                    'match' => 'products.*',
                    'icon' => 'P',
                    'screen' => 'products',
                ],
                [
                    'label' => 'Categories',
                    'route' => 'product-categories.index',
                    'match' => 'product-categories.*',
                    'icon' => 'C',
                    'screen' => 'product-categories',
                ],
                [
                    'label' => 'Suppliers',
                    'route' => 'suppliers.index',
                    'match' => 'suppliers.*',
                    'icon' => 'U',
                    'screen' => 'suppliers',
                ],
                [
                    'label' => 'Opening Stock',
                    'route' => 'opening-stock.create',
                    'match' => 'opening-stock.*',
                    'icon' => 'O',
                    'screen' => 'opening-stock',
                ],
                [
                    'label' => 'Purchase Receipts',
                    'route' => 'purchase-receipts.index',
                    'match' => 'purchase-receipts.*',
                    'icon' => 'R',
                    'screen' => 'purchase-receipts',
                ],
                [
                    'label' => 'Stock Ledger',
                    'route' => 'stock.ledger',
                    'match' => 'stock.*',
                    'icon' => 'L',
                    'screen' => 'stock',
                ],
                [
                    'label' => 'Low-Stock Alerts',
                    'route' => 'stock-control.low-stock',
                    'match' => 'stock-control.low-stock',
                    'icon' => 'A',
                    'screen' => 'stock-control.low-stock',
                ],
                [
                    'label' => 'Expiry Alerts',
                    'route' => 'stock-control.expiry',
                    'match' => 'stock-control.expiry',
                    'icon' => 'E',
                    'screen' => 'stock-control.expiry',
                ],
                [
                    'label' => 'Stock Counts',
                    'route' => 'stock-counts.index',
                    'match' => 'stock-counts.*',
                    'icon' => 'N',
                    'screen' => 'stock-counts',
                ],
            ],
        ],
        'configurations' => [
            'label' => 'Configurations',
            'items' => [
                [
                    'label' => 'Users',
                    'route' => 'admin.users.index',
                    'match' => 'admin.users.*',
                    'icon' => 'U',
                    'screen' => 'admin.users',
                ],
                [
                    'label' => 'Roles & Permissions',
                    'route' => 'admin.roles.index',
                    'match' => 'admin.roles.*',
                    'icon' => 'R',
                    'screen' => 'admin.roles',
                ],
                [
                    'label' => 'Income Categories',
                    'route' => 'finance.income-categories.index',
                    'match' => 'finance.income-categories.*',
                    'icon' => 'I',
                    'screen' => 'finance.income-categories',
                ],
                [
                    'label' => 'Expense Categories',
                    'route' => 'finance.expense-categories.index',
                    'match' => 'finance.expense-categories.*',
                    'icon' => 'X',
                    'screen' => 'finance.expense-categories',
                ],
            ],
        ],
        'finance' => [
            'label' => 'Finance',
            'items' => [
                [
                    'label' => 'Income',
                    'route' => 'finance.income.index',
                    'match' => 'finance.income.*',
                    'icon' => 'N',
                    'screen' => 'finance.income',
                ],
                [
                    'label' => 'Expenses',
                    'route' => 'finance.expenses.index',
                    'match' => 'finance.expenses.*',
                    'icon' => 'E',
                    'screen' => 'finance.expenses',
                ],
            ],
        ],
        'reports' => [
            'label' => 'Reports',
            'items' => [
                [
                    'label' => 'Finance Summary',
                    'route' => 'reports.finance-summary',
                    'match' => 'reports.finance-summary',
                    'icon' => 'F',
                    'screen' => 'reports.finance-summary',
                ],
                [
                    'label' => 'Income Report',
                    'route' => 'reports.finance-income',
                    'match' => 'reports.finance-income',
                    'icon' => 'R',
                    'screen' => 'reports.finance-income',
                ],
                [
                    'label' => 'Expense Report',
                    'route' => 'reports.finance-expenses',
                    'match' => 'reports.finance-expenses',
                    'icon' => 'P',
                    'screen' => 'reports.finance-expenses',
                ],
                [
                    'label' => 'Stock Reports',
                    'route' => 'reports.stock-on-hand',
                    'match' => 'reports.stock-*',
                    'icon' => 'T',
                    'screen' => 'reports.stock',
                ],
            ],
        ],
    ],
];
