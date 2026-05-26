@extends('layouts.app')

@section('title', 'Expiry Report')
@section('page-title', 'Stock Reports')

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-semibold text-[#191c1d]">Expiry Report</h1>
        <p class="mt-1 text-sm text-[#3e494a]">Expired and near-expiry stock rows from the expiry alert service.</p>
    </div>

    <form class="mb-4 grid grid-cols-4 gap-3 rounded-lg border border-[#bec8ca] bg-white p-4">
        <input name="search" value="{{ $filters['search'] }}" placeholder="Search product or SKU" class="rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm">
        <select name="category_id" class="rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm">
            <option value="">All categories</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((int) ($filters['category_id'] ?? 0) === $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <select name="days" class="rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm">
            @foreach ([30, 60, 90] as $days)
                <option value="{{ $days }}" @selected((int) $filters['days'] === $days)>Within {{ $days }} days</option>
            @endforeach
        </select>
        <button class="rounded-xl bg-[#00535b] px-4 py-3 text-sm font-semibold text-white">Filter</button>
    </form>

    <div class="overflow-hidden rounded-lg border border-[#bec8ca] bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-[#f3f4f5] text-xs uppercase tracking-[0.06em] text-[#3e494a]">
                <tr>
                    <th class="px-5 py-3">Product</th>
                    <th class="px-5 py-3">Batch</th>
                    <th class="px-5 py-3">Expiry Date</th>
                    <th class="px-5 py-3">Days Remaining</th>
                    <th class="px-5 py-3">Quantity</th>
                    <th class="px-5 py-3">Severity</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#bec8ca]">
                @forelse ($alerts as $alert)
                    <tr>
                        <td class="px-5 py-4 font-medium">{{ $alert['product']->name }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $alert['batch_number'] ?: '-' }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $alert['expires_at']->format('M d, Y') }}</td>
                        <td class="px-5 py-4">{{ $alert['days_until_expiry'] }}</td>
                        <td class="px-5 py-4 text-[#00535b]">{{ $alert['formatted_remaining_quantity'] }}</td>
                        <td class="px-5 py-4">{{ str_replace('_', ' ', $alert['severity']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-[#3e494a]">No expiry rows found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
