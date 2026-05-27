@extends('layouts.app')

@section('title', $expenseEntry->exists ? 'Edit Expense' : 'Record Expense')
@section('page-title', 'Expenses')

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-semibold text-[#191c1d]">{{ $expenseEntry->exists ? 'Edit Expense Entry' : 'Record Expense Entry' }}</h1>
        <p class="mt-1 text-sm text-[#3e494a]">Expenses are financial records only. They do not change products, stock balances, or the stock ledger.</p>
    </div>

    @error('form')
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
    @enderror

    <form method="POST" action="{{ $expenseEntry->exists ? route('finance.expenses.update', $expenseEntry) : route('finance.expenses.store') }}" class="max-w-2xl rounded-lg border border-[#bec8ca] bg-white p-6">
        @csrf
        @if ($expenseEntry->exists)
            @method('PUT')
        @endif

        <div class="space-y-5">
            <div>
                <label class="mb-2 block text-sm font-medium">Expense Category</label>
                <select name="expense_category_id" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]" required>
                    <option value="">Select category</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('expense_category_id', $expenseEntry->expense_category_id) == $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                @error('expense_category_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium">Amount</label>
                <input type="number" name="amount" value="{{ old('amount', $expenseEntry->amount) }}" min="0.01" step="0.01" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]" required>
                @error('amount')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium">Expense Date</label>
                <input type="date" name="expense_date" value="{{ old('expense_date', $expenseEntry->expense_date?->format('Y-m-d')) }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]" required>
                @error('expense_date')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium">Payee <span class="font-normal text-[#3e494a]">(optional)</span></label>
                <input name="payee" value="{{ old('payee', $expenseEntry->payee) }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">
                @error('payee')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium">Payment Method</label>
                <select name="payment_method" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]" required>
                    @foreach (\App\Models\ExpenseEntry::paymentMethods() as $method)
                        <option value="{{ $method }}" @selected(old('payment_method', $expenseEntry->payment_method) === $method)>
                            {{ ucfirst(str_replace('_', ' ', $method)) }}
                        </option>
                    @endforeach
                </select>
                @error('payment_method')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium">Description</label>
                <textarea name="description" rows="3" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">{{ old('description', $expenseEntry->description) }}</textarea>
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">Save</button>
            <a href="{{ route('finance.expenses.index') }}" class="rounded-xl border border-[#bec8ca] px-4 py-2 text-sm text-[#3e494a]">Cancel</a>
        </div>
    </form>
@endsection
