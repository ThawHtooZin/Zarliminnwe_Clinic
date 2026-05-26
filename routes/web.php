<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Catalog\ProductCategoryController;
use App\Http\Controllers\Catalog\ProductController;
use App\Http\Controllers\Catalog\SupplierController;
use App\Http\Controllers\Inventory\OpeningStockController;
use App\Http\Controllers\Inventory\PurchaseReceiptController;
use App\Http\Controllers\Inventory\StockController;
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
        Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
        Route::get('/sales/pos', [PosController::class, 'create'])->name('sales.pos');
        Route::post('/sales/hold', [PosController::class, 'hold'])->name('sales.hold');
        Route::post('/sales', [PosController::class, 'store'])->name('sales.store');
        Route::get('/sales/products/search', ProductSearchController::class)->name('sales.products.search');
        Route::get('/sales/{sale}/resume', [PosController::class, 'resume'])->name('sales.resume');
        Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
        Route::get('/sales/{sale}/receipt', [SaleReceiptController::class, 'show'])->name('sales.receipt');
    });

    Route::middleware('role:admin,pharmacist')->group(function () {
        Route::post('/sales/{sale}/void', [SaleVoidController::class, 'store'])->name('sales.void');
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
    });
});
