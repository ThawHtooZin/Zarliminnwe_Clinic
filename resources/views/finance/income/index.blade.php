@extends('layouts.app')

@section('title', 'Income')
@section('page-title', 'Income')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-[#191c1d]">Income Entries</h1>
            <p class="mt-1 text-sm text-[#3e494a]">Service income and completed pharmacy POS sales in one list. Sales are not duplicated into income entries.</p>
        </div>
        <a href="{{ route('finance.income.create') }}" class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">Record Service Income</a>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-[#bec8ca] bg-white px-4 py-3 text-sm text-[#00535b]">{{ session('status') }}</div>
    @endif

    <form method="GET" action="{{ route('finance.income.index') }}" class="mb-5 rounded-lg border border-[#bec8ca] bg-white p-4">
        <div class="grid gap-4 md:grid-cols-3 lg:grid-cols-6">
            <div>
                <label class="mb-2 block text-sm font-medium">Received From</label>
                <input type="date" name="received_from" value="{{ $filters['received_from'] ?? '' }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium">Received To</label>
                <input type="date" name="received_to" value="{{ $filters['received_to'] ?? '' }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium">Category</label>
                <select name="income_category_id" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">
                    <option value="">All</option>
                    <option value="{{ \App\Domain\Finance\Services\UnifiedIncomeQueryService::PHARMACY_SALE_FILTER }}" @selected(($filters['income_category_id'] ?? '') === \App\Domain\Finance\Services\UnifiedIncomeQueryService::PHARMACY_SALE_FILTER)>Pharmacy Sale</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(($filters['income_category_id'] ?? '') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium">Payment</label>
                <select name="payment_method" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">
                    <option value="">All</option>
                    @foreach (\App\Models\IncomeEntry::paymentMethods() as $method)
                        <option value="{{ $method }}" @selected(($filters['payment_method'] ?? '') === $method)>{{ ucfirst(str_replace('_', ' ', $method)) }}</option>
                    @endforeach
                    <option value="{{ \App\Models\Sale::PAYMENT_MIXED }}" @selected(($filters['payment_method'] ?? '') === \App\Models\Sale::PAYMENT_MIXED)>Mixed</option>
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium">Patient Visit</label>
                <select name="patient_visit_id" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">
                    <option value="">All</option>
                    @foreach ($patientVisits as $visit)
                        <option value="{{ $visit->id }}" @selected(($filters['patient_visit_id'] ?? '') == $visit->id)>
                            {{ $visit->patient_name }} ({{ $visit->age }}) — {{ $visit->visited_at->format('M d, Y H:i') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium">Received By</label>
                <select name="received_by" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">
                    <option value="">All</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(($filters['received_by'] ?? '') == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-4 flex gap-2">
            <button class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">Filter</button>
            <a href="{{ route('finance.income.index') }}" class="rounded-xl border border-[#bec8ca] px-4 py-2 text-sm text-[#3e494a]">Reset</a>
        </div>
    </form>

    @include('finance.income._unified-income-table', ['lines' => $unifiedIncomeLines, 'showActions' => true])
@endsection
