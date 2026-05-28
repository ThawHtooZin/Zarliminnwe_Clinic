@extends('layouts.app')

@section('title', 'Expenses')
@section('page-title', 'Expenses')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-[#191c1d]">Expense Entries</h1>
            <p class="mt-1 text-sm text-[#3e494a]">Clinic and pharmacy operating expenses. These do not affect inventory or stock.</p>
        </div>
        <a href="{{ route('finance.expenses.create') }}" class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">Record Expense</a>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-[#bec8ca] bg-white px-4 py-3 text-sm text-[#00535b]">{{ session('status') }}</div>
    @endif

    <form method="GET" action="{{ route('finance.expenses.index') }}" class="mb-5 rounded-lg border border-[#bec8ca] bg-white p-4">
        <div class="grid gap-4 md:grid-cols-3 lg:grid-cols-6">
            <div>
                <label class="mb-2 block text-sm font-medium">Expense From</label>
                <input type="date" name="expense_from" value="{{ $filters['expense_from'] ?? '' }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium">Expense To</label>
                <input type="date" name="expense_to" value="{{ $filters['expense_to'] ?? '' }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">
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
            <a href="{{ route('finance.expenses.index') }}" class="rounded-xl border border-[#bec8ca] px-4 py-2 text-sm text-[#3e494a]">Reset</a>
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
                    <th class="px-5 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#bec8ca]">
                @forelse ($expenseEntries as $entry)
                    <tr>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $entry->expense_date->format('M d, Y') }}</td>
                        <td class="px-5 py-4 font-medium text-[#191c1d]">{{ $entry->expenseCategory->name }}</td>
                        <td class="px-5 py-4 font-medium text-[#191c1d]">{{ number_format($entry->amount, 2) }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $entry->payee ?: '—' }}</td>
                        <td class="px-5 py-4 capitalize text-[#3e494a]">{{ str_replace('_', ' ', $entry->payment_method) }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $entry->createdBy?->name ?: '—' }}</td>
                        <td class="px-5 py-4 text-right">
                            <a href="{{ route('finance.expenses.edit', $entry) }}" class="font-medium text-[#00535b]">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-8 text-center text-[#3e494a]">No expense entries yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $expenseEntries->links() }}</div>
@endsection
