@extends('layouts.app')

@section('title', 'Stock')
@section('page-title', 'Stock')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-[#191c1d]">Stock On Hand</h1>
            <p class="mt-1 text-sm text-[#3e494a]">Inventory is tracked by the actual unit received or posted.</p>
        </div>
        <a href="{{ route('opening-stock.create') }}" class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">Post Opening Stock</a>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-[#bec8ca] bg-white px-4 py-3 text-sm text-[#00535b]">{{ session('status') }}</div>
    @endif

    <form class="mb-4">
        <input name="search" value="{{ request('search') }}" placeholder="Search by product or SKU" class="w-full max-w-md rounded-xl border border-[#bec8ca] bg-white px-4 py-3 text-sm outline-none focus:border-[#00535b]">
    </form>

    <div class="overflow-hidden rounded-lg border border-[#bec8ca] bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-[#f3f4f5] text-xs uppercase tracking-[0.06em] text-[#3e494a]">
                <tr>
                    <th class="px-5 py-3">Product</th>
                    <th class="px-5 py-3">SKU</th>
                    <th class="px-5 py-3">Stock</th>
                    <th class="px-5 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#bec8ca]">
                @forelse ($products as $product)
                    <tr>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <x-product-image :product="$product" size="sm" />
                                <span class="font-medium">{{ $product->name }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $product->sku }}</td>
                        <td class="px-5 py-4 text-[#00535b]">{{ $product->formatted_stock }}</td>
                        <td class="px-5 py-4">{{ $product->is_active ? 'Active' : 'Inactive' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-8 text-center text-[#3e494a]">No products found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $products->links() }}</div>
@endsection
