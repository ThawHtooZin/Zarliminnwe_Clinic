<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\View\View;

class SaleReceiptController extends Controller
{
    public function show(Sale $sale): View
    {
        $sale->load(['cashier', 'lines.product', 'lines.productUnit']);

        return view('sales.receipt', compact('sale'));
    }
}
