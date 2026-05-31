<?php

namespace App\Http\Controllers\Inventory;

use App\Domain\Inventory\Services\StockCountService;
use App\Http\Controllers\Controller;
use App\Http\Support\UnauthorizedResponse;
use App\Models\StockCount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class StockCountWorkflowController extends Controller
{
    public function __construct(private readonly StockCountService $stockCountService) {}

    public function submit(Request $request, StockCount $stockCount): RedirectResponse
    {
        try {
            $this->stockCountService->submit($stockCount, $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['stock_count' => $exception->getMessage()]);
        }

        return redirect()->route('stock-counts.show', $stockCount)->with('status', 'Stock count submitted for review.');
    }

    public function post(Request $request, StockCount $stockCount): RedirectResponse
    {
        if (! $request->user()->hasRole(User::ROLE_ADMIN, User::ROLE_STOCK_MANAGER)) {
            return UnauthorizedResponse::deny($request);
        }

        try {
            $this->stockCountService->post($stockCount, $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['stock_count' => $exception->getMessage()]);
        }

        return redirect()->route('stock-counts.show', $stockCount)->with('status', 'Stock count posted.');
    }

    public function cancel(Request $request, StockCount $stockCount): RedirectResponse
    {
        if (! $request->user()->hasRole(User::ROLE_ADMIN, User::ROLE_STOCK_MANAGER)) {
            return UnauthorizedResponse::deny($request);
        }

        try {
            $this->stockCountService->cancel($stockCount);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['stock_count' => $exception->getMessage()]);
        }

        return redirect()->route('stock-counts.show', $stockCount)->with('status', 'Stock count cancelled.');
    }
}
