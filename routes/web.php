<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Catalog\ProductCategoryController;
use App\Http\Controllers\Catalog\ProductController;
use App\Http\Controllers\Catalog\SupplierController;
use App\Http\Controllers\Finance\ExpenseCategoryController;
use App\Http\Controllers\Finance\ExpenseEntryController;
use App\Http\Controllers\Finance\IncomeCategoryController;
use App\Http\Controllers\Finance\IncomeEntryController;
use App\Http\Controllers\Inventory\ExpiryAlertController;
use App\Http\Controllers\Inventory\LowStockAlertController;
use App\Http\Controllers\Inventory\OpeningStockController;
use App\Http\Controllers\Inventory\PurchaseReceiptController;
use App\Http\Controllers\Inventory\StockAdjustmentController;
use App\Http\Controllers\Inventory\StockController;
use App\Http\Controllers\Inventory\StockCountController;
use App\Http\Controllers\Inventory\StockCountWorkflowController;
use App\Http\Controllers\Patients\PatientVisitController;
use App\Http\Controllers\Reports\FinanceReportController;
use App\Http\Controllers\Reports\StockReportController;
use App\Http\Controllers\Sales\PosController;
use App\Http\Controllers\Sales\ProductSearchController;
use App\Http\Controllers\Sales\SaleController;
use App\Http\Controllers\Sales\SaleReceiptController;
use App\Http\Controllers\Sales\SaleVoidController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::middleware('role:admin,pharmacist,cashier')->group(function () {
        Route::resource('patient-visits', PatientVisitController::class)->except(['destroy']);

        Route::prefix('finance')->name('finance.')->group(function () {
            Route::resource('income', IncomeEntryController::class)
                ->except(['show', 'destroy'])
                ->parameters(['income' => 'incomeEntry']);
            Route::resource('expenses', ExpenseEntryController::class)
                ->except(['show', 'destroy'])
                ->parameters(['expenses' => 'expenseEntry']);
        });

        Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
        Route::get('/sales/pos', [PosController::class, 'create'])->name('sales.pos');
        Route::post('/sales/hold', [PosController::class, 'hold'])->name('sales.hold');
        Route::post('/sales', [PosController::class, 'store'])->name('sales.store');
        Route::get('/sales/products/search', ProductSearchController::class)->name('sales.products.search');
        Route::get('/sales/{sale}/resume', [PosController::class, 'resume'])->name('sales.resume');
        Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
        Route::get('/sales/{sale}/receipt', [SaleReceiptController::class, 'show'])->name('sales.receipt');

        Route::get('/reports/finance-income', [FinanceReportController::class, 'incomeReport'])->name('reports.finance-income');
        Route::get('/reports/finance-expenses', [FinanceReportController::class, 'expenseReport'])->name('reports.finance-expenses');
    });

    Route::middleware('role:admin,pharmacist')->group(function () {
        Route::post('/sales/{sale}/void', [SaleVoidController::class, 'store'])->name('sales.void');

        Route::get('/reports/finance-summary', [FinanceReportController::class, 'financeSummary'])->name('reports.finance-summary');

        Route::prefix('finance')->name('finance.')->group(function () {
            Route::resource('income-categories', IncomeCategoryController::class)->except(['show', 'destroy']);
            Route::resource('expense-categories', ExpenseCategoryController::class)->except(['show', 'destroy']);
        });
    });

    Route::middleware('role:admin,stock_manager,pharmacist')->group(function () {
        Route::resource('product-categories', ProductCategoryController::class)->except(['show', 'destroy']);
        Route::resource('products', ProductController::class)->except(['destroy']);
        Route::resource('suppliers', SupplierController::class)->except(['show', 'destroy']);

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
    });
});
