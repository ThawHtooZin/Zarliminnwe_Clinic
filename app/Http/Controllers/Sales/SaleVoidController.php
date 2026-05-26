<?php

namespace App\Http\Controllers\Sales;

use App\Domain\Sales\Services\SaleVoidService;
use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class SaleVoidController extends Controller
{
    public function __construct(private readonly SaleVoidService $saleVoidService) {}

    public function store(Request $request, Sale $sale): RedirectResponse
    {
        $validated = $request->validate([
            'void_reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $sale = $this->saleVoidService->void($sale, $request->user(), $validated['void_reason']);
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors(['void' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('sales.show', $sale)
            ->with('status', 'Sale '.$sale->sale_number.' voided.');
    }
}
