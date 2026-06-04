<?php

use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\AiHelperController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Catalog\ProductCategoryController;
use App\Http\Controllers\Catalog\ProductController;
use App\Http\Controllers\Catalog\SupplierController;
use App\Http\Controllers\Finance\ExpenseCategoryController;
use App\Http\Controllers\Finance\ExpenseEntryController;
use App\Http\Controllers\Finance\IncomeCategoryController;
use App\Http\Controllers\Finance\IncomeEntryController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\Inventory\ExpiryAlertController;
use App\Http\Controllers\Inventory\LowStockAlertController;
use App\Http\Controllers\Inventory\OpeningStockController;
use App\Http\Controllers\Inventory\PurchaseReceiptController;
use App\Http\Controllers\Inventory\StockAdjustmentController;
use App\Http\Controllers\Inventory\StockController;
use App\Http\Controllers\Inventory\StockCountController;
use App\Http\Controllers\Inventory\StockCountWorkflowController;
use App\Http\Controllers\Patients\PatientController;
use App\Http\Controllers\Patients\PatientVisitController;
use App\Http\Controllers\Reports\FinanceReportController;
use App\Http\Controllers\Reports\StockReportController;
use App\Http\Controllers\Sales\PosController;
use App\Http\Controllers\Sales\ProductSearchController;
use App\Http\Controllers\Sales\SaleController;
use App\Http\Controllers\Sales\SaleReceiptController;
use App\Http\Controllers\Sales\SaleVoidController;
use App\Http\Controllers\BackupRestore\BackupRestoreController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/ai-helper/chat', [AiHelperController::class, 'chat'])->name('ai-helper.chat');
});

Route::middleware(['auth', 'permission.route'])->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/help', [HelpController::class, 'index'])->name('help.index');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('patients', PatientController::class)->except(['destroy']);
    Route::get('patients/{patient}/visit-records/create', [PatientVisitController::class, 'createForPatient'])->name('patients.visit-records.create');
    Route::post('patients/{patient}/visit-records', [PatientVisitController::class, 'storeForPatient'])->name('patients.visit-records.store');
    Route::get('patients/{patient}/visit-records/{patientVisit}/edit', [PatientVisitController::class, 'editForPatient'])->name('patients.visit-records.edit');
    Route::put('patients/{patient}/visit-records/{patientVisit}', [PatientVisitController::class, 'updateForPatient'])->name('patients.visit-records.update');
    Route::get('patient-visits/{patientVisit}', [PatientVisitController::class, 'show'])->name('patient-visits.show');
    Route::post('patient-visits/{patientVisit}/diagnoses', [PatientVisitController::class, 'storeDiagnosis'])->name('patient-visits.diagnoses.store');
    Route::get('patient-visits/{patientVisit}/diagnoses/{diagnosis}/edit', [PatientVisitController::class, 'editDiagnosis'])->name('patient-visits.diagnoses.edit');
    Route::put('patient-visits/{patientVisit}/diagnoses/{diagnosis}', [PatientVisitController::class, 'updateDiagnosis'])->name('patient-visits.diagnoses.update');
    Route::get('sales/patient-visits/today-recent', [PatientVisitController::class, 'todayRecent'])->name('sales.patient-visits.today-recent');

    Route::prefix('finance')->name('finance.')->group(function () {
        Route::resource('income', IncomeEntryController::class)
            ->except(['show', 'destroy'])
            ->parameters(['income' => 'incomeEntry']);
        Route::resource('expenses', ExpenseEntryController::class)
            ->except(['show', 'destroy'])
            ->parameters(['expenses' => 'expenseEntry']);
        Route::resource('income-categories', IncomeCategoryController::class)->except(['show']);
        Route::resource('expense-categories', ExpenseCategoryController::class)->except(['show']);
    });

    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/sales/pos', [PosController::class, 'create'])->name('sales.pos');
    Route::post('/sales/hold', [PosController::class, 'hold'])->name('sales.hold');
    Route::post('/sales', [PosController::class, 'store'])->name('sales.store');
    Route::get('/sales/products/search', ProductSearchController::class)->name('sales.products.search');
    Route::get('/sales/{sale}/resume', [PosController::class, 'resume'])->name('sales.resume');
    Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
    Route::get('/sales/{sale}/receipt', [SaleReceiptController::class, 'show'])->name('sales.receipt');
    Route::post('/sales/{sale}/void', [SaleVoidController::class, 'store'])->name('sales.void');

    Route::get('/reports/finance-income', [FinanceReportController::class, 'incomeReport'])->name('reports.finance-income');
    Route::get('/reports/finance-expenses', [FinanceReportController::class, 'expenseReport'])->name('reports.finance-expenses');
    Route::get('/reports/finance-summary', [FinanceReportController::class, 'financeSummary'])->name('reports.finance-summary');

    Route::resource('product-categories', ProductCategoryController::class)->except(['show']);
    Route::resource('products', ProductController::class);
    Route::resource('suppliers', SupplierController::class)->except(['show']);

    Route::get('/opening-stock', [OpeningStockController::class, 'create'])->name('opening-stock.create');
    Route::post('/opening-stock', [OpeningStockController::class, 'store'])->name('opening-stock.store');

    Route::resource('purchase-receipts', PurchaseReceiptController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('/purchase-receipts/{purchaseReceipt}/post', [PurchaseReceiptController::class, 'post'])->name('purchase-receipts.post');

    Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
    Route::get('/stock/ledger', [StockController::class, 'ledger'])->name('stock.ledger');
    Route::get('/stock-control/low-stock', [LowStockAlertController::class, 'index'])->name('stock-control.low-stock');
    Route::get('/stock-control/expiry', [ExpiryAlertController::class, 'index'])->name('stock-control.expiry');
    Route::get('/stock-adjustments/create', [StockAdjustmentController::class, 'create'])->name('stock-adjustments.create');
    Route::post('/stock-adjustments', [StockAdjustmentController::class, 'store'])->name('stock-adjustments.store');

    Route::resource('stock-counts', StockCountController::class)->only(['index', 'create', 'store', 'show', 'update']);
    Route::post('/stock-counts/{stockCount}/submit', [StockCountWorkflowController::class, 'submit'])->name('stock-counts.submit');
    Route::post('/stock-counts/{stockCount}/post', [StockCountWorkflowController::class, 'post'])->name('stock-counts.post');
    Route::post('/stock-counts/{stockCount}/cancel', [StockCountWorkflowController::class, 'cancel'])->name('stock-counts.cancel');

    Route::get('/reports/stock-on-hand', [StockReportController::class, 'stockOnHand'])->name('reports.stock-on-hand');
    Route::get('/reports/stock-movements', [StockReportController::class, 'stockMovements'])->name('reports.stock-movements');
    Route::get('/reports/low-stock', [StockReportController::class, 'lowStock'])->name('reports.low-stock');
    Route::get('/reports/expiry', [StockReportController::class, 'expiry'])->name('reports.expiry');
    Route::get('/reports/stock-adjustments', [StockReportController::class, 'adjustments'])->name('reports.stock-adjustments');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::resource('roles', RoleController::class)->only(['index', 'edit', 'update']);
    });

    Route::prefix('backup-restore')->name('backup-restore.')->group(function () {
        Route::get('/', [BackupRestoreController::class, 'index'])->name('index');
        Route::get('/datasets/{dataset}/export.csv', [BackupRestoreController::class, 'exportCsv'])->name('export.csv');
        Route::get('/datasets/{dataset}/export.sql', [BackupRestoreController::class, 'exportSql'])->name('export.sql');
        Route::post('/datasets/{dataset}/import', [BackupRestoreController::class, 'import'])->name('import');
        Route::post('/datasets/{dataset}/restore.sql', [BackupRestoreController::class, 'restoreSql'])->name('restore.sql');
        Route::get('/database/export.sql', [BackupRestoreController::class, 'exportDatabase'])->name('database.export');
        Route::post('/database/restore.sql', [BackupRestoreController::class, 'restoreDatabase'])->name('database.restore');
    });
});
