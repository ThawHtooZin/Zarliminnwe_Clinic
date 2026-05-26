@extends('layouts.app')

@section('title', 'Stock On Hand Report')
@section('page-title', 'Stock Reports')

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-semibold text-[#191c1d]">Stock On Hand Report</h1>
        <p class="mt-1 text-sm text-[#3e494a]">Current stock balances by product, batch, and unit.</p>
    </div>

    <form class="mb-4 grid grid-cols-5 gap-3 rounded-lg border border-[#bec8ca] bg-white p-4">
        <input name="search" value="{{ $filters['search'] }}" placeholder="Search product or SKU" class="rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm">
        <select name="category_id" class="rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm">
            <option value="">All categories</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((int) ($filters['category_id'] ?? 0) === $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <select name="batch_id" class="rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm">
            <option value="">All batches</option>
            @foreach ($batches as $batch)
                <option value="{{ $batch->id }}" @selected((int) ($filters['batch_id'] ?? 0) === $batch->id)>{{ $batch->batch_number ?: 'No batch' }}</option>
            @endforeach
        </select>
        <select name="active_status" class="rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm">
            <option value="">All statuses</option>
            <option value="active" @selected($filters['active_status'] === 'active')>Active</option>
            <option value="inactive" @selected($filters['active_status'] === 'inactive')>Inactive</option>
        </select>
        <button class="rounded-xl bg-[#00535b] px-4 py-3 text-sm font-semibold text-white">Filter</button>
    </form>

    <div class="overflow-hidden rounded-lg border border-[#bec8ca] bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-[#f3f4f5] text-xs uppercase tracking-[0.06em] text-[#3e494a]">
                <tr>
                    <th class="px-5 py-3">Product</th>
                    <th class="px-5 py-3">SKU</th>
                    <th class="px-5 py-3">Category</th>
                    <th class="px-5 py-3">Batch</th>
                    <th class="px-5 py-3">Unit</th>
                    <th class="px-5 py-3">Quantity</th>
                    <th class="px-5 py-3">Formatted Stock</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#bec8ca]">
                @forelse ($balances as $balance)
                    <tr>
                        <td class="px-5 py-4 font-medium">{{ $balance->product->name }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $balance->product->sku }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $balance->product->category?->name ?: '-' }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $balance->stockBatch?->batch_number ?: '-' }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $balance->productUnit->name }}</td>
                        <td class="px-5 py-4 text-[#00535b]">{{ rtrim(rtrim($balance->quantity, '0'), '.') }}</td>
                        <td class="px-5 py-4">{{ rtrim(rtrim($balance->quantity, '0'), '.') }} {{ $balance->productUnit->abbreviation }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-8 text-center text-[#3e494a]">No stock balances found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $balances->links() }}</div>
@endsection
