@extends('layouts.app')

@section('title', 'Expense Report')
@section('page-title', 'Expense Report')

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-semibold text-[#191c1d]">Expense Report</h1>
        <p class="mt-1 text-sm text-[#3e494a]">Operating expenses for the selected date range.</p>
    </div>

    <form method="GET" action="{{ route('reports.finance-expenses') }}" class="mb-5 rounded-lg border border-[#bec8ca] bg-white p-4">
        <div class="grid gap-4 md:grid-cols-3 lg:grid-cols-6">
            <div>
                <label class="mb-2 block text-sm font-medium">From</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium">To</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium">Category</label>
                <select name="expense_category_id" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">
                    <option value="">All</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(($filters['expense_category_id'] ?? '') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium">Payment</label>
                <select name="payment_method" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">
                    <option value="">All</option>
                    @foreach (\App\Models\ExpenseEntry::paymentMethods() as $method)
                        <option value="{{ $method }}" @selected(($filters['payment_method'] ?? '') === $method)>{{ ucfirst(str_replace('_', ' ', $method)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium">Payee</label>
                <input name="payee" value="{{ $filters['payee'] ?? '' }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium">Created By</label>
                <select name="created_by" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">
                    <option value="">All</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(($filters['created_by'] ?? '') == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-4 flex gap-2">
            <button class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">Filter</button>
            <a href="{{ route('reports.finance-expenses') }}" class="rounded-xl border border-[#bec8ca] px-4 py-2 text-sm text-[#3e494a]">Reset</a>
        </div>
    </form>

    <div class="overflow-hidden rounded-lg border border-[#bec8ca] bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-[#f3f4f5] text-xs uppercase tracking-[0.06em] text-[#3e494a]">
                <tr>
                    <th class="px-5 py-3">Date</th>
                    <th class="px-5 py-3">Category</th>
                    <th class="px-5 py-3">Amount</th>
                    <th class="px-5 py-3">Payee</th>
                    <th class="px-5 py-3">Payment</th>
                    <th class="px-5 py-3">Created By</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#bec8ca]">
                @forelse ($expenseEntries as $entry)
                    <tr>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $entry->expense_date->format('M d, Y') }}</td>
                        <td class="px-5 py-4 font-medium">{{ $entry->expenseCategory->name }}</td>
                        <td class="px-5 py-4 font-medium">{{ number_format($entry->amount, 2) }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $entry->payee ?: '—' }}</td>
                        <td class="px-5 py-4 capitalize">{{ str_replace('_', ' ', $entry->payment_method) }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $entry->createdBy?->name ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-[#3e494a]">No expense entries in range.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $expenseEntries->links() }}</div>
@endsection
