@extends('layouts.app')

@section('title', 'Income Report')
@section('page-title', 'Income Report')

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-semibold text-[#191c1d]">Income Report</h1>
        <p class="mt-1 text-sm text-[#3e494a]">Service and general income entries only. Pharmacy sales are on the finance summary report.</p>
    </div>

    <form method="GET" action="{{ route('reports.finance-income') }}" class="mb-5 rounded-lg border border-[#bec8ca] bg-white p-4">
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
                <select name="income_category_id" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">
                    <option value="">All</option>
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
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium">Patient Visit</label>
                <select name="patient_visit_id" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">
                    <option value="">All</option>
                    @foreach ($patientVisits as $visit)
                        <option value="{{ $visit->id }}" @selected(($filters['patient_visit_id'] ?? '') == $visit->id)>{{ $visit->patient_name }}</option>
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
            <a href="{{ route('reports.finance-income') }}" class="rounded-xl border border-[#bec8ca] px-4 py-2 text-sm text-[#3e494a]">Reset</a>
        </div>
    </form>

    <div class="overflow-hidden rounded-lg border border-[#bec8ca] bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-[#f3f4f5] text-xs uppercase tracking-[0.06em] text-[#3e494a]">
                <tr>
                    <th class="px-5 py-3">Received</th>
                    <th class="px-5 py-3">Category</th>
                    <th class="px-5 py-3">Type</th>
                    <th class="px-5 py-3">Patient Visit</th>
                    <th class="px-5 py-3">Amount</th>
                    <th class="px-5 py-3">Payment</th>
                    <th class="px-5 py-3">User</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#bec8ca]">
                @forelse ($incomeEntries as $entry)
                    <tr>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $entry->received_at->format('M d, Y H:i') }}</td>
                        <td class="px-5 py-4 font-medium">{{ $entry->incomeCategory->name }}</td>
                        <td class="px-5 py-4 capitalize text-[#3e494a]">{{ $entry->incomeCategory->type }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">
                            @if ($entry->patientVisit)
                                {{ $entry->patientVisit->patient_name }} ({{ $entry->patientVisit->age }})<br>
                                <span class="text-xs">{{ $entry->patientVisit->visited_at->format('M d, Y H:i') }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-5 py-4 font-medium">{{ number_format($entry->amount, 2) }}</td>
                        <td class="px-5 py-4 capitalize">{{ str_replace('_', ' ', $entry->payment_method) }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $entry->receivedBy?->name ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-8 text-center text-[#3e494a]">No income entries in range.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $incomeEntries->links() }}</div>
@endsection
