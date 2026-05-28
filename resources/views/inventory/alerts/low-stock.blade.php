@extends('layouts.app')

@section('title', 'Low-Stock Alerts')
@section('page-title', 'Stock Control')

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-semibold text-[#191c1d]">Low-Stock Alerts</h1>
        <p class="mt-1 text-sm text-[#3e494a]">Products below their configured reorder quantity, compared in the reorder unit.</p>
    </div>

    <form class="mb-4 grid grid-cols-4 gap-3 rounded-lg border border-[#bec8ca] bg-white p-4">
        <div>
            <label class="mb-2 block text-xs font-medium uppercase tracking-[0.06em] text-[#3e494a]">Product</label>
            <input name="search" value="{{ $filters['search'] }}" placeholder="Search name, SKU, or generic" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">
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
            <label class="mb-2 block text-xs font-medium uppercase tracking-[0.06em] text-[#3e494a]">Status</label>
            <select name="active_status" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm">
                <option value="">All statuses</option>
                <option value="active" @selected($filters['active_status'] === 'active')>Active</option>
                <option value="inactive" @selected($filters['active_status'] === 'inactive')>Inactive</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button class="rounded-xl bg-[#00535b] px-4 py-3 text-sm font-semibold text-white">Apply Filters</button>
            <a href="{{ route('stock-control.low-stock') }}" class="rounded-xl border border-[#bec8ca] px-4 py-3 text-sm text-[#3e494a]">Reset</a>
        </div>
    </form>

    <div class="mb-4 rounded-xl border border-[#bec8ca] bg-white px-4 py-3 text-sm text-[#00535b]">
        {{ $alerts->count() }} {{ Str::plural('product', $alerts->count()) }} below reorder threshold.
    </div>

    <div class="overflow-hidden rounded-lg border border-[#bec8ca] bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-[#f3f4f5] text-xs uppercase tracking-[0.06em] text-[#3e494a]">
                <tr>
                    <th class="px-5 py-3">Product</th>
                    <th class="px-5 py-3">SKU</th>
                    <th class="px-5 py-3">Category</th>
                    <th class="px-5 py-3">Current Stock</th>
                    <th class="px-5 py-3">Reorder Unit</th>
                    <th class="px-5 py-3">Reorder Qty</th>
                    <th class="px-5 py-3">Available</th>
                    <th class="px-5 py-3">Shortage</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#bec8ca]">
                @forelse ($alerts as $alert)
                    @php($product = $alert['product'])
                    <tr>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <x-product-image :product="$product" size="sm" />
                                <div>
                                    <p class="font-medium text-[#191c1d]">{{ $product->name }}</p>
                                    <p class="text-xs text-[#3e494a]">{{ $product->is_active ? 'Active' : 'Inactive' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $product->sku }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $product->category?->name ?: '-' }}</td>
                        <td class="px-5 py-4 text-[#00535b]">{{ $alert['formatted_stock'] }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $alert['reorder_unit']->name }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ number_format($alert['reorder_quantity'], 4, '.', '') }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $alert['formatted_available_stock'] }}</td>
                        <td class="px-5 py-4 font-semibold text-red-700">{{ $alert['formatted_shortage'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-5 py-8 text-center text-[#3e494a]">No low-stock products found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
