<?php

namespace App\Http\Controllers\Inventory;

use App\Domain\Inventory\Services\LowStockAlertService;
use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LowStockAlertController extends Controller
{
    public function __construct(private readonly LowStockAlertService $lowStockAlertService) {}

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'category_id' => $request->integer('category_id') ?: null,
            'active_status' => $request->string('active_status')->toString(),
        ];

        return view('inventory.alerts.low-stock', [
            'alerts' => $this->lowStockAlertService->getLowStockProducts($filters),
            'categories' => ProductCategory::orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }
}
