@extends('layouts.app')

@section('title', 'Income')
@section('page-title', 'Income')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-[#191c1d]">Income Entries</h1>
            <p class="mt-1 text-sm text-[#3e494a]">Service and general clinic income. Pharmacy POS sales are recorded separately.</p>
        </div>
        <a href="{{ route('finance.income.create') }}" class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">Record Income</a>
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

    <div class="overflow-hidden rounded-lg border border-[#bec8ca] bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-[#f3f4f5] text-xs uppercase tracking-[0.06em] text-[#3e494a]">
                <tr>
                    <th class="px-5 py-3">Received</th>
                    <th class="px-5 py-3">Category</th>
                    <th class="px-5 py-3">Patient Visit</th>
                    <th class="px-5 py-3">Amount</th>
                    <th class="px-5 py-3">Payment</th>
                    <th class="px-5 py-3">Received By</th>
                    <th class="px-5 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#bec8ca]">
                @forelse ($incomeEntries as $entry)
                    <tr>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $entry->received_at->format('M d, Y H:i') }}</td>
                        <td class="px-5 py-4">
                            <span class="font-medium text-[#191c1d]">{{ $entry->incomeCategory->name }}</span>
                            <span class="block text-xs capitalize text-[#3e494a]">{{ $entry->incomeCategory->type }}</span>
                        </td>
                        <td class="px-5 py-4 text-[#3e494a]">
                            @if ($entry->patientVisit)
                                <a href="{{ route('patient-visits.show', $entry->patientVisit) }}" class="text-[#00535b]">
                                    {{ $entry->patientVisit->patient_name }} ({{ $entry->patientVisit->age }})
                                </a>
                                <span class="block text-xs">{{ $entry->patientVisit->visited_at->format('M d, Y H:i') }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-5 py-4 font-medium text-[#191c1d]">{{ number_format($entry->amount, 2) }}</td>
                        <td class="px-5 py-4 capitalize text-[#3e494a]">{{ str_replace('_', ' ', $entry->payment_method) }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $entry->receivedBy?->name ?: '—' }}</td>
                        <td class="px-5 py-4 text-right">
                            <a href="{{ route('finance.income.edit', $entry) }}" class="font-medium text-[#00535b]">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-8 text-center text-[#3e494a]">No income entries yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $incomeEntries->links() }}</div>
@endsection
