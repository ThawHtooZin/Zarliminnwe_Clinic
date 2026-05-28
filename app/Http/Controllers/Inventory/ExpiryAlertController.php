<?php

namespace App\Http\Controllers\Inventory;

use App\Domain\Inventory\Services\ExpiryAlertService;
use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpiryAlertController extends Controller
{
    public function __construct(private readonly ExpiryAlertService $expiryAlertService) {}

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'category_id' => $request->integer('category_id') ?: null,
            'expired_only' => $request->boolean('expired_only'),
        ];
        $days = max($request->integer('days', 90), 1);

        return view('inventory.alerts.expiry', [
            'alerts' => $this->expiryAlertService->getExpiringBatches($days, $filters),
            'categories' => ProductCategory::orderBy('name')->get(),
            'filters' => $filters + ['days' => $days],
        ]);
    }
}
