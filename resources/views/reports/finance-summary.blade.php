@extends('layouts.app')

@section('title', 'Finance Summary')
@section('page-title', 'Finance Summary')

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-semibold text-[#191c1d]">Finance Summary</h1>
        <p class="mt-1 text-sm text-[#3e494a]">Service and general income come from income entries. Pharmacy POS sales are read separately and are never duplicated into income entries.</p>
    </div>

    <form method="GET" action="{{ route('reports.finance-summary') }}" class="mb-5 rounded-lg border border-[#bec8ca] bg-white p-4">
        <div class="grid gap-4 md:grid-cols-3 lg:grid-cols-5">
            <div>
                <label class="mb-2 block text-sm font-medium">From</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium">To</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium">Income Category</label>
                <select name="income_category_id" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">
                    <option value="">All</option>
                    @foreach ($incomeCategories as $category)
                        <option value="{{ $category->id }}" @selected(($filters['income_category_id'] ?? '') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium">Expense Category</label>
                <select name="expense_category_id" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">
                    <option value="">All</option>
                    @foreach ($expenseCategories as $category)
                        <option value="{{ $category->id }}" @selected(($filters['expense_category_id'] ?? '') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium">Payment Method</label>
                <select name="payment_method" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">
                    <option value="">All</option>
                    @foreach (\App\Models\IncomeEntry::paymentMethods() as $method)
                        <option value="{{ $method }}" @selected(($filters['payment_method'] ?? '') === $method)>{{ ucfirst(str_replace('_', ' ', $method)) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-4 flex gap-2">
            <button class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">Apply</button>
            <a href="{{ route('reports.finance-summary') }}" class="rounded-xl border border-[#bec8ca] px-4 py-2 text-sm text-[#3e494a]">Reset</a>
        </div>
    </form>

    <div class="mb-6 grid gap-4 md:grid-cols-3 lg:grid-cols-6">
        <div class="rounded-lg border border-[#bec8ca] bg-white p-4">
            <p class="text-xs uppercase tracking-[0.06em] text-[#3e494a]">Service Income</p>
            <p class="mt-2 text-2xl font-semibold text-[#191c1d]">{{ number_format($summary['service_income'], 2) }}</p>
        </div>
        <div class="rounded-lg border border-[#bec8ca] bg-white p-4">
            <p class="text-xs uppercase tracking-[0.06em] text-[#3e494a]">General Income</p>
            <p class="mt-2 text-2xl font-semibold text-[#191c1d]">{{ number_format($summary['general_income'], 2) }}</p>
        </div>
        <div class="rounded-lg border border-[#bec8ca] bg-white p-4">
            <p class="text-xs uppercase tracking-[0.06em] text-[#3e494a]">Pharmacy Sales (POS)</p>
            <p class="mt-2 text-2xl font-semibold text-[#191c1d]">{{ number_format($summary['pharmacy_sales_income'], 2) }}</p>
        </div>
        <div class="rounded-lg border border-[#bec8ca] bg-white p-4">
            <p class="text-xs uppercase tracking-[0.06em] text-[#3e494a]">Total Income</p>
            <p class="mt-2 text-2xl font-semibold text-[#00535b]">{{ number_format($summary['total_income'], 2) }}</p>
        </div>
        <div class="rounded-lg border border-[#bec8ca] bg-white p-4">
            <p class="text-xs uppercase tracking-[0.06em] text-[#3e494a]">Total Expenses</p>
            <p class="mt-2 text-2xl font-semibold text-[#191c1d]">{{ number_format($summary['expense_total'], 2) }}</p>
        </div>
        <div class="rounded-lg border border-[#bec8ca] bg-white p-4">
            <p class="text-xs uppercase tracking-[0.06em] text-[#3e494a]">Net Balance</p>
            <p class="mt-2 text-2xl font-semibold {{ $summary['net_balance'] >= 0 ? 'text-[#00535b]' : 'text-red-600' }}">{{ number_format($summary['net_balance'], 2) }}</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-lg border border-[#bec8ca] bg-white p-4">
            <h2 class="mb-3 text-lg font-semibold text-[#191c1d]">Income by Category</h2>
            <table class="w-full text-left text-sm">
                <thead class="text-xs uppercase text-[#3e494a]">
                    <tr><th class="pb-2">Category</th><th class="pb-2">Type</th><th class="pb-2 text-right">Amount</th></tr>
                </thead>
                <tbody class="divide-y divide-[#bec8ca]">
                    @forelse ($summary['income_by_category'] as $row)
                        <tr>
                            <td class="py-2">{{ $row->name }}</td>
                            <td class="py-2 capitalize text-[#3e494a]">{{ $row->type }}</td>
                            <td class="py-2 text-right font-medium">{{ number_format($row->total_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-4 text-[#3e494a]">No income in range.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="rounded-lg border border-[#bec8ca] bg-white p-4">
            <h2 class="mb-3 text-lg font-semibold text-[#191c1d]">Expenses by Category</h2>
            <table class="w-full text-left text-sm">
                <thead class="text-xs uppercase text-[#3e494a]">
                    <tr><th class="pb-2">Category</th><th class="pb-2 text-right">Amount</th></tr>
                </thead>
                <tbody class="divide-y divide-[#bec8ca]">
                    @forelse ($summary['expense_by_category'] as $row)
                        <tr>
                            <td class="py-2">{{ $row->name }}</td>
                            <td class="py-2 text-right font-medium">{{ number_format($row->total_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="py-4 text-[#3e494a]">No expenses in range.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
