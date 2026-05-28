<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\StockLedger;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(Request $request): View
    {
        $sales = Sale::query()
            ->with(['cashier', 'patientVisitRecord.patient'])
            ->when($request->string('status')->toString(), function ($query, string $status): void {
                $query->where('status', $status);
            })
            ->when($request->date('date'), function ($query, $date): void {
                $query->whereDate('sold_at', $date);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('sales.index', [
            'sales' => $sales,
            'statuses' => [
                Sale::STATUS_COMPLETED,
                Sale::STATUS_HELD,
                Sale::STATUS_VOIDED,
            ],
        ]);
    }

    public function show(Sale $sale): View
    {
        $sale->load(['cashier', 'voidedBy', 'patientVisitRecord.patient', 'lines.product', 'lines.productUnit']);

        $stockMovements = StockLedger::query()
            ->with(['product', 'productUnit', 'stockBatch', 'creator'])
            ->where('reference_type', Sale::class)
            ->where('reference_id', $sale->id)
            ->latest()
            ->get();

        return view('sales.show', compact('sale', 'stockMovements'));
    }
}
