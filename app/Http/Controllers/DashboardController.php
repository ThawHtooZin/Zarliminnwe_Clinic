<?php

namespace App\Http\Controllers;

use App\Domain\Inventory\Services\ExpiryAlertService;
use App\Domain\Inventory\Services\LowStockAlertService;
use App\Models\ExpenseEntry;
use App\Models\IncomeEntry;
use App\Models\PatientVisitRecord;
use App\Models\Sale;
use App\Models\StockCount;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, LowStockAlertService $lowStockAlertService, ExpiryAlertService $expiryAlertService): View
    {
        $today = Carbon::today();
        $startDate = $today->copy()->subDays(6)->startOfDay();
        $endDate = $today->copy()->endOfDay();

        $lowStockCount = $lowStockAlertService->getLowStockProducts()->count();
        $expiringBatchesCount = $expiryAlertService->getExpiringBatches(30)->count();
        $pendingCounts = StockCount::query()
            ->whereIn('status', [StockCount::STATUS_DRAFT, StockCount::STATUS_SUBMITTED])
            ->count();

        $todayServiceIncome = (float) IncomeEntry::query()
            ->whereDate('received_at', $today)
            ->sum('amount');

        $todayPharmacySales = (float) Sale::query()
            ->where('status', Sale::STATUS_COMPLETED)
            ->whereDate('sold_at', $today)
            ->sum('grand_total');

        $todayExpenses = (float) ExpenseEntry::query()
            ->whereDate('expense_date', $today)
            ->sum('amount');

        $todayPatientVisits = PatientVisitRecord::query()
            ->whereDate('created_at', $today)
            ->count();

        $serviceRevenueByDay = IncomeEntry::query()
            ->selectRaw('DATE(received_at) as day, SUM(amount) as total')
            ->whereBetween('received_at', [$startDate, $endDate])
            ->groupBy('day')
            ->pluck('total', 'day');

        $pharmacyRevenueByDay = Sale::query()
            ->selectRaw('DATE(sold_at) as day, SUM(grand_total) as total')
            ->where('status', Sale::STATUS_COMPLETED)
            ->whereBetween('sold_at', [$startDate, $endDate])
            ->groupBy('day')
            ->pluck('total', 'day');

        $revenueTrend = collect(range(0, 6))
            ->map(function (int $offset) use ($startDate, $serviceRevenueByDay, $pharmacyRevenueByDay): array {
                $day = $startDate->copy()->addDays($offset);
                $key = $day->toDateString();
                $serviceAmount = (float) ($serviceRevenueByDay[$key] ?? 0);
                $pharmacyAmount = (float) ($pharmacyRevenueByDay[$key] ?? 0);

                return [
                    'date' => $key,
                    'label' => $day->format('M d'),
                    'total' => round($serviceAmount + $pharmacyAmount, 2),
                ];
            })
            ->values()
            ->all();

        return view('dashboard', [
            'lowStockCount' => $lowStockCount,
            'expiringBatchesCount' => $expiringBatchesCount,
            'pendingCounts' => $pendingCounts,
            'todayServiceIncome' => $todayServiceIncome,
            'todayPharmacySales' => $todayPharmacySales,
            'todayExpenses' => $todayExpenses,
            'todayPatientVisits' => $todayPatientVisits,
            'revenueTrend' => $revenueTrend,
        ]);
    }
}
