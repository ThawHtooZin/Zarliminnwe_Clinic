@extends('layouts.app')

@section('title', 'Help Center')
@section('page-title', 'Help Center')

@section('content')
    @php
        $sections = [
            [
                'screen' => 'Dashboard',
                'slug' => 'screen.dashboard',
                'purpose' => 'Show a quick daily overview so staff can see important activity at a glance.',
                'actions' => [
                    'View high-level business and clinic summary cards.',
                    'Open allowed modules from the sidebar.',
                ],
                'consequences' => [
                    'This is a read-only overview page.',
                    'No stock, finance, or patient records are changed here.',
                ],
                'steps' => [
                    'Login with a role that has dashboard access.',
                    'Open Dashboard.',
                    'Confirm summary blocks load without errors.',
                    'Click sidebar links and confirm only allowed screens are visible.',
                ],
            ],
            [
                'screen' => 'POS',
                'slug' => 'screen.sales.pos',
                'purpose' => 'Process pharmacy sales for active patient visits and walk-in sales.',
                'actions' => [
                    'Search products by name, SKU, generic name, or barcode.',
                    'Add a product/unit to cart.',
                    'Add the same product/unit again to increase quantity on the same row.',
                    'Select a patient from today\'s active visit list (optional).',
                    'Complete sale, hold sale, and resume held sale.',
                ],
                'consequences' => [
                    'Completing a sale deducts inventory immediately.',
                    'Stock always moves in whole numbers only.',
                    'If smaller-unit stock is short, the system auto-breaks a whole parent unit (for example, 1 strip into 10 capsules), sells what is needed, and keeps the remainder as stock.',
                    'No same-day appointment flow exists. Patient choice comes only from today\'s active visits.',
                    'Completed sales appear in sales history and related finance reporting views.',
                ],
                'steps' => [
                    'Open POS.',
                    'Search a product and add one unit to cart.',
                    'Add the same product/unit again and confirm quantity increases (no duplicate line).',
                    'Set quantity to a whole number only and confirm decimals are not used.',
                    'Select a patient from today\'s active visits.',
                    'Complete sale with valid payment.',
                    'Confirm success message and verify sale appears in Sales History.',
                ],
            ],
            [
                'screen' => 'Patients',
                'slug' => 'screen.patients',
                'purpose' => 'Manage patient profiles and create visit records.',
                'actions' => [
                    'Create a new patient profile.',
                    'Search and view existing patient profiles.',
                    'Edit patient profile details.',
                    'Create a new visit inside a patient profile.',
                    'Edit an existing visit and diagnosis details from patient/visit pages.',
                ],
                'consequences' => [
                    'Patients are created only here.',
                    'Visits are created only from inside a patient profile.',
                    'This keeps the clinic workflow linear: Patient -> Visit -> POS.',
                ],
                'steps' => [
                    'Open Patients and create a new patient.',
                    'Open that patient profile.',
                    'Create a visit record from the profile.',
                    'Add or edit diagnosis details on the visit.',
                    'Go to POS and confirm today\'s active visit can be selected.',
                ],
            ],
            [
                'screen' => 'Sales History',
                'slug' => 'screen.sales.index',
                'purpose' => 'Review completed, held, and voided sales.',
                'actions' => [
                    'View sales list and details.',
                    'Filter by status/date (if shown for your role).',
                    'Open sale receipt.',
                    'Void sale (only if your role is allowed).',
                ],
                'consequences' => [
                    'Voiding a sale reverses its stock impact.',
                    'Sales history is the source for receipt review and audit checks.',
                ],
                'steps' => [
                    'Complete at least one sale in POS.',
                    'Open Sales History.',
                    'Open that sale and receipt.',
                    'If your role can void, void one sale and confirm status changes to voided.',
                ],
            ],
            [
                'screen' => 'Income',
                'slug' => 'screen.finance.income',
                'purpose' => 'Track service/general income records and view pharmacy sales in unified income view.',
                'actions' => [
                    'View income list with filters.',
                    'Record a new service/general income entry.',
                    'Edit an income entry.',
                ],
                'consequences' => [
                    'Pharmacy sales are shown in income reporting automatically after POS completion.',
                    'Staff should not manually re-enter pharmacy sale amounts as income entries.',
                ],
                'steps' => [
                    'Open Income screen.',
                    'Create a service income entry with amount and category.',
                    'Save and confirm it appears in the list.',
                    'Complete a POS sale and confirm pharmacy sale also appears in unified income view.',
                ],
            ],
            [
                'screen' => 'Expenses',
                'slug' => 'screen.finance.expenses',
                'purpose' => 'Record and review business expenses.',
                'actions' => [
                    'View expense list.',
                    'Create a new expense entry.',
                    'Edit an existing expense entry.',
                ],
                'consequences' => [
                    'Expense records affect finance reporting totals.',
                ],
                'steps' => [
                    'Open Expenses.',
                    'Create an expense with category, amount, and date.',
                    'Save and confirm it appears in list.',
                    'Edit it and confirm updates are saved.',
                ],
            ],
            [
                'screen' => 'Income Categories',
                'slug' => 'screen.finance.income-categories',
                'purpose' => 'Manage selectable category names for income entries.',
                'actions' => [
                    'Create category.',
                    'Edit category.',
                    'View category list.',
                ],
                'consequences' => [
                    'New categories become available in the Income entry form.',
                ],
                'steps' => [
                    'Open Income Categories.',
                    'Add a new category.',
                    'Open Income form and confirm new category appears.',
                    'Edit category name and confirm list updates.',
                ],
            ],
            [
                'screen' => 'Expense Categories',
                'slug' => 'screen.finance.expense-categories',
                'purpose' => 'Manage selectable category names for expense entries.',
                'actions' => [
                    'Create category.',
                    'Edit category.',
                    'View category list.',
                ],
                'consequences' => [
                    'New categories become available in the Expense entry form.',
                ],
                'steps' => [
                    'Open Expense Categories.',
                    'Add a new category.',
                    'Open Expense form and confirm new category appears.',
                    'Edit category name and confirm list updates.',
                ],
            ],
            [
                'screen' => 'Stock Ledger',
                'slug' => 'screen.stock',
                'purpose' => 'Review current stock and stock movement history.',
                'actions' => [
                    'View stock on hand.',
                    'View stock movement/ledger records.',
                    'Filter and review movement entries.',
                ],
                'consequences' => [
                    'Ledger is the main audit trail for stock changes from opening stock, purchase receipts, sales, voids, and adjustments.',
                ],
                'steps' => [
                    'Post opening stock for a product.',
                    'Complete a POS sale.',
                    'Open Stock Ledger.',
                    'Confirm both in/out movements are visible in history.',
                ],
            ],
            [
                'screen' => 'Opening Stock',
                'slug' => 'screen.opening-stock',
                'purpose' => 'Enter initial stock quantities for products.',
                'actions' => [
                    'Select product and unit.',
                    'Enter opening quantity.',
                    'Save opening stock entry.',
                ],
                'consequences' => [
                    'Product stock on hand increases immediately.',
                    'Opening stock becomes part of the stock audit history.',
                ],
                'steps' => [
                    'Open Opening Stock.',
                    'Choose product and unit.',
                    'Enter whole-number quantity and save.',
                    'Check Stock screen to confirm on-hand quantity increased.',
                ],
            ],
            [
                'screen' => 'Purchase Receipts',
                'slug' => 'screen.purchase-receipts',
                'purpose' => 'Record supplier deliveries and post received stock.',
                'actions' => [
                    'Create purchase receipt with supplier and line items.',
                    'Save receipt.',
                    'Post receipt.',
                    'View receipt details.',
                ],
                'consequences' => [
                    'Posting receipt increases stock in the exact received units.',
                    'Posted receipts affect stock reports and availability.',
                ],
                'steps' => [
                    'Open Purchase Receipts and create a new receipt.',
                    'Add product, unit, quantity, and cost.',
                    'Save, then post the receipt.',
                    'Verify stock increase in Stock Ledger / stock views.',
                ],
            ],
            [
                'screen' => 'Stock Counts',
                'slug' => 'screen.stock-counts',
                'purpose' => 'Run physical stock counting and finalize differences.',
                'actions' => [
                    'Start a stock count.',
                    'Enter counted quantities.',
                    'Submit for review/posting.',
                    'Post or cancel based on allowed flow.',
                ],
                'consequences' => [
                    'Posting count applies stock corrections to match physical count.',
                ],
                'steps' => [
                    'Create a new stock count.',
                    'Enter counted values for sample products.',
                    'Submit and post the stock count.',
                    'Confirm stock quantities reflect posted count result.',
                ],
            ],
            [
                'screen' => 'Low-Stock Alerts',
                'slug' => 'screen.stock-control.low-stock',
                'purpose' => 'Show products that are close to stock-out.',
                'actions' => [
                    'View low-stock list.',
                    'Review urgent items for replenishment.',
                ],
                'consequences' => [
                    'Read-only alert view to support purchasing decisions.',
                ],
                'steps' => [
                    'Ensure one product stock is below expected threshold.',
                    'Open Low-Stock Alerts.',
                    'Confirm low-stock item appears.',
                ],
            ],
            [
                'screen' => 'Expiry Alerts',
                'slug' => 'screen.stock-control.expiry',
                'purpose' => 'Show products nearing expiry dates.',
                'actions' => [
                    'View expiring items list.',
                    'Review by date urgency.',
                ],
                'consequences' => [
                    'Read-only alert view to reduce wastage and unsafe dispensing.',
                ],
                'steps' => [
                    'Create stock with near expiry date.',
                    'Open Expiry Alerts.',
                    'Confirm the item appears in alert list.',
                ],
            ],
            [
                'screen' => 'Products',
                'slug' => 'screen.products',
                'purpose' => 'Manage product catalog and unit sale setup.',
                'actions' => [
                    'Create product.',
                    'Edit product details.',
                    'Configure unit levels and pricing.',
                    'View product list/details.',
                ],
                'consequences' => [
                    'Product/unit setup drives POS selling, stock handling, and reports.',
                ],
                'steps' => [
                    'Create a new product.',
                    'Add unit hierarchy (example: box -> strip -> capsule) with whole-number conversion.',
                    'Set sale prices.',
                    'Confirm product appears in POS search.',
                ],
            ],
            [
                'screen' => 'Product Categories',
                'slug' => 'screen.product-categories',
                'purpose' => 'Group products under category labels.',
                'actions' => [
                    'Create category.',
                    'Edit category.',
                    'View list.',
                ],
                'consequences' => [
                    'Categories help product organization and filtering.',
                ],
                'steps' => [
                    'Open Product Categories.',
                    'Add a category.',
                    'Create/edit a product and assign that category.',
                    'Confirm category shows correctly in product list/form.',
                ],
            ],
            [
                'screen' => 'Suppliers',
                'slug' => 'screen.suppliers',
                'purpose' => 'Manage supplier records used in purchasing.',
                'actions' => [
                    'Create supplier.',
                    'Edit supplier.',
                    'View supplier list.',
                ],
                'consequences' => [
                    'Supplier info is used when creating purchase receipts.',
                ],
                'steps' => [
                    'Add a new supplier.',
                    'Open Purchase Receipt form.',
                    'Confirm the new supplier is selectable.',
                ],
            ],
            [
                'screen' => 'Roles & Permissions',
                'slug' => 'screen.admin.roles',
                'purpose' => 'Control what each role can see and do.',
                'actions' => [
                    'Open role permission editor.',
                    'Toggle screen permissions.',
                    'Toggle route permissions.',
                    'Save role permission changes.',
                ],
                'consequences' => [
                    'Turning off a screen also disables and clears related routes automatically.',
                    'Access changes apply immediately to users with that role.',
                ],
                'steps' => [
                    'Open Roles & Permissions and choose a role.',
                    'Uncheck one screen permission (example: Income).',
                    'Confirm related route permissions auto-uncheck/disable.',
                    'Save and login as that role.',
                    'Confirm that screen and related routes are no longer accessible.',
                ],
            ],
            [
                'screen' => 'Users',
                'slug' => 'screen.admin.users',
                'purpose' => 'Manage staff accounts and role assignment.',
                'actions' => [
                    'Create user account.',
                    'Edit user details and role.',
                    'Activate/deactivate account.',
                    'Reset password.',
                ],
                'consequences' => [
                    'Role assignment immediately controls screen and route access.',
                    'Deactivated users cannot sign in.',
                ],
                'steps' => [
                    'Create a user with cashier role.',
                    'Login as that user and verify allowed screens only.',
                    'Change role to another role and verify access changes.',
                    'Deactivate user and confirm login is blocked.',
                ],
            ],
            [
                'screen' => 'Finance Summary',
                'slug' => 'screen.reports.finance-summary',
                'purpose' => 'Show summary-level finance totals and trends.',
                'actions' => [
                    'View overall finance summary for selected period.',
                    'Review totals for decision making.',
                ],
                'consequences' => [
                    'Read-only report screen combining finance data points.',
                ],
                'steps' => [
                    'Create sample income, expense, and POS sale entries.',
                    'Open Finance Summary report.',
                    'Confirm totals reflect entered data.',
                ],
            ],
            [
                'screen' => 'Income Report',
                'slug' => 'screen.reports.finance-income',
                'purpose' => 'Show detailed income report lines.',
                'actions' => [
                    'View filtered income report.',
                    'Review service income and pharmacy-sale income together.',
                ],
                'consequences' => [
                    'Completed POS sales flow into this report automatically.',
                ],
                'steps' => [
                    'Record service income.',
                    'Complete a POS sale.',
                    'Open Income Report.',
                    'Confirm both income sources appear.',
                ],
            ],
            [
                'screen' => 'Expense Report',
                'slug' => 'screen.reports.finance-expenses',
                'purpose' => 'Show detailed expense report lines.',
                'actions' => [
                    'View and filter expense report.',
                    'Review expense trends.',
                ],
                'consequences' => [
                    'Read-only reporting based on entered expense records.',
                ],
                'steps' => [
                    'Record multiple expense entries.',
                    'Open Expense Report.',
                    'Apply filters and verify expected totals/list.',
                ],
            ],
            [
                'screen' => 'Stock Reports',
                'slug' => 'screen.reports.stock',
                'purpose' => 'Provide stock-focused reporting views for operations.',
                'actions' => [
                    'View stock on hand report.',
                    'View stock movements report.',
                    'View low-stock, expiry, and stock adjustment reports.',
                ],
                'consequences' => [
                    'Read-only reporting from stock activity and current balances.',
                ],
                'steps' => [
                    'Perform opening stock, purchase receipt posting, POS sale, and one adjustment/count flow.',
                    'Open Stock Reports screens.',
                    'Confirm each report reflects the matching activity.',
                ],
            ],
            [
                'screen' => 'Workflow Rules (Phase 6 Staff Reminder)',
                'slug' => null,
                'purpose' => 'Core workflow rules all staff must follow every day.',
                'actions' => [
                    'No same-day appointment workflow is used.',
                    'Patient flow is strict: Patient -> Visit -> POS.',
                    'Patients are created only in Patients.',
                    'Visits are created only inside a patient profile.',
                    'POS patient dropdown shows only today\'s active visits.',
                    'POS stock deduction is whole-number only with automatic parent-unit breakdown when needed.',
                ],
                'consequences' => [
                    'Following these rules keeps patient, stock, and finance data consistent across modules.',
                ],
                'steps' => [
                    'Create patient in Patients module.',
                    'Create visit inside that patient profile.',
                    'Open POS and select today\'s active visit only.',
                    'Complete sale and verify stock/finance records stay in sync.',
                ],
            ],
        ];
    @endphp

    <div class="min-h-[calc(100vh-7rem)] rounded-2xl bg-gray-50 p-4 sm:p-6 lg:p-8">
        <div class="mx-auto max-w-6xl">
            <div class="rounded-2xl border border-[#d4d9da] bg-white p-6 shadow-sm sm:p-8">
                <h1 class="text-2xl font-bold text-[#00535b] sm:text-3xl">Master User Manual &amp; Help Center</h1>
                <p class="mt-3 text-sm leading-6 text-[#3e494a] sm:text-base">
                    This guide is organized by permission screen slug so clinic teams can train staff and test each area clearly.
                </p>
                <div class="mt-5 rounded-xl border border-[#d4d9da] bg-gray-50 px-4 py-3 text-xs font-medium text-[#3e494a] sm:text-sm">
                    Tap any card to expand or collapse details. This keeps reading focused and easier during staff training.
                </div>
            </div>

            <div class="mt-6 space-y-4">
                @foreach ($sections as $section)
                    <section x-data="{ open: false }" class="overflow-hidden rounded-2xl border border-[#d4d9da] bg-white shadow-sm">
                        <button
                            type="button"
                            class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left sm:px-6"
                            x-on:click="open = !open"
                            :aria-expanded="open.toString()"
                        >
                            <div>
                                <h2 class="text-base font-semibold text-[#00535b] sm:text-lg">{{ $section['screen'] }}</h2>
                                @if ($section['slug'])
                                    <p class="mt-1 text-xs text-[#6f797a] sm:text-sm">{{ $section['slug'] }}</p>
                                @endif
                            </div>
                            <span
                                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[#bec8ca] bg-gray-50 text-[#00535b] transition"
                                :class="open ? 'rotate-180' : ''"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.71a.75.75 0 1 1 1.06 1.06l-4.24 4.25a.75.75 0 0 1-1.06 0L5.21 8.29a.75.75 0 0 1 .02-1.08Z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </button>

                        <div
                            x-show="open"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-1"
                            class="border-t border-[#e1e3e4] bg-gray-50 px-5 py-5 sm:px-6"
                            style="display: none;"
                        >
                            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                                <div class="rounded-xl border border-[#d4d9da] bg-white p-4">
                                    <h3 class="text-sm font-semibold uppercase tracking-wide text-[#00535b]">Purpose</h3>
                                    <p class="mt-2 text-sm leading-6 text-[#3e494a]">{{ $section['purpose'] }}</p>
                                </div>

                                <div class="rounded-xl border border-[#d4d9da] bg-white p-4">
                                    <h3 class="text-sm font-semibold uppercase tracking-wide text-[#00535b]">Available Actions</h3>
                                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm leading-6 text-[#3e494a]">
                                        @foreach ($section['actions'] as $action)
                                            <li>{{ $action }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>

                            <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-2">
                                <div class="rounded-xl border border-[#d4d9da] bg-white p-4">
                                    <h3 class="text-sm font-semibold uppercase tracking-wide text-[#00535b]">Consequences</h3>
                                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm leading-6 text-[#3e494a]">
                                        @foreach ($section['consequences'] as $consequence)
                                            <li>{{ $consequence }}</li>
                                        @endforeach
                                    </ul>
                                </div>

                                <div class="rounded-xl border border-[#d4d9da] bg-white p-4">
                                    <h3 class="text-sm font-semibold uppercase tracking-wide text-[#00535b]">Step-by-Step Test Flow</h3>
                                    <ol class="mt-2 list-decimal space-y-1 pl-5 text-sm leading-6 text-[#3e494a]">
                                        @foreach ($section['steps'] as $step)
                                            <li>{{ $step }}</li>
                                        @endforeach
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    </div>
@endsection
