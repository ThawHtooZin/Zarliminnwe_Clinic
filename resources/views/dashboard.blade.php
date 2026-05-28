@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    <div class="space-y-6">
        <section class="space-y-3">
            <div>
                <h2 class="text-lg font-semibold text-[#00535b]">Actionable Alerts</h2>
                <p class="text-sm text-gray-600">Priority operational items that need immediate staff attention.</p>
            </div>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-600">Low Stock</p>
                    <p class="mt-2 text-3xl font-bold text-[#00535b]">{{ number_format($lowStockCount) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Products below reorder threshold</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-600">Expiring Batches</p>
                    <p class="mt-2 text-3xl font-bold text-[#00535b]">{{ number_format($expiringBatchesCount) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Expiring within 30 days or already expired</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-600">Pending Counts</p>
                    <p class="mt-2 text-3xl font-bold text-[#00535b]">{{ number_format($pendingCounts) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Stock counts in draft or submitted status</p>
                </div>
            </div>
        </section>

        <section class="space-y-3">
            <div>
                <h2 class="text-lg font-semibold text-[#00535b]">Today's Overview</h2>
                <p class="text-sm text-gray-600">Daily finance and clinic activity at a glance.</p>
            </div>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-600">Today's Income</p>
                    <div class="mt-3 space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">Service Income</span>
                            <span class="font-semibold text-[#00535b]">{{ number_format($todayServiceIncome, 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">Pharmacy Sales</span>
                            <span class="font-semibold text-[#00535b]">{{ number_format($todayPharmacySales, 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between border-t border-gray-200 pt-2 text-sm">
                            <span class="font-medium text-gray-700">Total</span>
                            <span class="text-base font-bold text-[#00535b]">{{ number_format($todayServiceIncome + $todayPharmacySales, 2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-600">Today's Expenses</p>
                    <p class="mt-3 text-3xl font-bold text-[#00535b]">{{ number_format($todayExpenses, 2) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Total expense entries posted today</p>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-600">Today's Patient Visits</p>
                    <p class="mt-3 text-3xl font-bold text-[#00535b]">{{ number_format($todayPatientVisits) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Visit records created today</p>
                </div>
            </div>
        </section>

        <section class="space-y-3">
            <div>
                <h2 class="text-lg font-semibold text-[#00535b]">7-Day Revenue Trend</h2>
                <p class="text-sm text-gray-600">Combined service income and completed pharmacy sales.</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="h-64 sm:h-72">
                    <canvas id="revenueTrendChart"></canvas>
                </div>
            </div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (() => {
            const trend = @json($revenueTrend);
            const ctx = document.getElementById('revenueTrendChart');

            if (!ctx) {
                return;
            }

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: trend.map((item) => item.label),
                    datasets: [{
                        label: 'Revenue',
                        data: trend.map((item) => Number(item.total)),
                        backgroundColor: '#00535b',
                        borderColor: '#00535b',
                        borderWidth: 1,
                        borderRadius: 4,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false,
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#e5e7eb',
                            },
                            ticks: {
                                color: '#6b7280',
                            },
                        },
                        x: {
                            grid: {
                                display: false,
                            },
                            ticks: {
                                color: '#6b7280',
                            },
                        },
                    },
                },
            });
        })();
    </script>
@endsection
