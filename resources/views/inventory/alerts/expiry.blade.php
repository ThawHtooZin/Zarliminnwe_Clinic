@extends('layouts.app')

@section('title', 'Expiry Alerts')
@section('page-title', 'Stock Control')

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-semibold text-[#191c1d]">Expiry Alerts</h1>
        <p class="mt-1 text-sm text-[#3e494a]">Expired and near-expiry batches with remaining stock. Expired stock is removed only through explicit adjustment.</p>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-[#bec8ca] bg-white px-4 py-3 text-sm text-[#00535b]">{{ session('status') }}</div>
    @endif

    <form class="mb-4 grid grid-cols-5 gap-3 rounded-lg border border-[#bec8ca] bg-white p-4">
        <div>
            <label class="mb-2 block text-xs font-medium uppercase tracking-[0.06em] text-[#3e494a]">Product</label>
            <input name="search" value="{{ $filters['search'] }}" placeholder="Search product or SKU" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm">
        </div>
        <div>
            <label class="mb-2 block text-xs font-medium uppercase tracking-[0.06em] text-[#3e494a]">Category</label>
            <select name="category_id" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((int) ($filters['category_id'] ?? 0) === $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-2 block text-xs font-medium uppercase tracking-[0.06em] text-[#3e494a]">Window</label>
            <select name="days" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm">
                @foreach ([30, 60, 90] as $days)
                    <option value="{{ $days }}" @selected((int) $filters['days'] === $days)>Within {{ $days }} days</option>
                @endforeach
            </select>
        </div>
        <label class="mt-8 flex items-center gap-2 text-sm text-[#3e494a]">
            <input type="checkbox" name="expired_only" value="1" @checked($filters['expired_only'])>
            Expired only
        </label>
        <div class="flex items-end gap-2">
            <button class="rounded-xl bg-[#00535b] px-4 py-3 text-sm font-semibold text-white">Apply</button>
            <a href="{{ route('stock-control.expiry') }}" class="rounded-xl border border-[#bec8ca] px-4 py-3 text-sm text-[#3e494a]">Reset</a>
        </div>
    </form>

    <div class="overflow-hidden rounded-lg border border-[#bec8ca] bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-[#f3f4f5] text-xs uppercase tracking-[0.06em] text-[#3e494a]">
                <tr>
                    <th class="px-5 py-3">Product</th>
                    <th class="px-5 py-3">Batch</th>
                    <th class="px-5 py-3">Expiry Date</th>
                    <th class="px-5 py-3">Days</th>
                    <th class="px-5 py-3">Remaining</th>
                    <th class="px-5 py-3">Severity</th>
                    <th class="px-5 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#bec8ca]">
                @forelse ($alerts as $alert)
                    <tr>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <x-product-image :product="$alert['product']" size="sm" />
                                <div>
                                    <p class="font-medium text-[#191c1d]">{{ $alert['product']->name }}</p>
                                    <p class="text-xs text-[#3e494a]">{{ $alert['product']->sku }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $alert['batch_number'] ?: '-' }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $alert['expires_at']->format('M d, Y') }}</td>
                        <td class="px-5 py-4">{{ $alert['days_until_expiry'] }}</td>
                        <td class="px-5 py-4 text-[#00535b]">{{ $alert['formatted_remaining_quantity'] }}</td>
                        <td class="px-5 py-4">{{ str_replace('_', ' ', $alert['severity']) }}</td>
                        <td class="px-5 py-4 text-right">
                            <a href="{{ route('stock-adjustments.create', ['stock_balance_id' => $alert['balance']->id]) }}" class="font-medium text-[#00535b]">Adjust</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-8 text-center text-[#3e494a]">No expiring batches found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
