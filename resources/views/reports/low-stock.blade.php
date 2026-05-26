@extends('layouts.app')

@section('title', 'Low-Stock Report')
@section('page-title', 'Stock Reports')

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-semibold text-[#191c1d]">Low-Stock Report</h1>
        <p class="mt-1 text-sm text-[#3e494a]">Reuses the low-stock alert service for threshold and shortage reporting.</p>
    </div>

    <form class="mb-4 grid grid-cols-3 gap-3 rounded-lg border border-[#bec8ca] bg-white p-4">
        <input name="search" value="{{ $filters['search'] }}" placeholder="Search product or SKU" class="rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm">
        <select name="category_id" class="rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm">
            <option value="">All categories</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((int) ($filters['category_id'] ?? 0) === $category->id)>{{ $category->name }}</option>
            @endforeach
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
                    <th class="px-5 py-3">Current Stock</th>
                    <th class="px-5 py-3">Reorder Threshold</th>
                    <th class="px-5 py-3">Shortage</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#bec8ca]">
                @forelse ($alerts as $alert)
                    <tr>
                        <td class="px-5 py-4 font-medium">{{ $alert['product']->name }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $alert['product']->sku }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $alert['product']->category?->name ?: '-' }}</td>
                        <td class="px-5 py-4 text-[#00535b]">{{ $alert['formatted_stock'] }}</td>
                        <td class="px-5 py-4">{{ rtrim(rtrim(number_format($alert['reorder_quantity'], 6, '.', ''), '0'), '.') }} {{ $alert['reorder_unit']->abbreviation }}</td>
                        <td class="px-5 py-4 font-semibold text-red-700">{{ $alert['formatted_shortage'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-[#3e494a]">No low-stock products found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
