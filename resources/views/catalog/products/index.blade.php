@extends('layouts.app')

@section('title', 'Products')
@section('page-title', 'Products')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-[#191c1d]">Products</h1>
            <p class="mt-1 text-sm text-[#3e494a]">Manage pharmacy items and their unit levels.</p>
        </div>
        <a href="{{ route('products.create') }}" class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">New Product</a>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-[#bec8ca] bg-white px-4 py-3 text-sm text-[#00535b]">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <form class="mb-4">
        <input name="search" value="{{ request('search') }}" placeholder="Search by name, SKU, or generic name" class="w-full max-w-md rounded-xl border border-[#bec8ca] bg-white px-4 py-3 text-sm outline-none focus:border-[#00535b]">
    </form>

    <div class="overflow-hidden rounded-lg border border-[#bec8ca] bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-[#f3f4f5] text-xs uppercase tracking-[0.06em] text-[#3e494a]">
                <tr>
                    <th class="px-5 py-3">Product</th>
                    <th class="px-5 py-3">SKU</th>
                    <th class="px-5 py-3">Category</th>
                    <th class="px-5 py-3">Units</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#bec8ca]">
                @forelse ($products as $product)
                    <tr>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <x-product-image :product="$product" size="sm" />
                                <div>
                                    <p class="font-medium text-[#191c1d]">{{ $product->name }}</p>
                                    <p class="text-xs text-[#3e494a]">{{ $product->generic_name ?: 'No generic name' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $product->sku }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $product->category?->name }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $product->units->pluck('abbreviation')->implode(', ') ?: '-' }}</td>
                        <td class="px-5 py-4">{{ $product->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="px-5 py-4 text-right space-x-3">
                            <a href="{{ route('products.show', $product) }}" class="font-medium text-[#00535b]">View</a>
                            <a href="{{ route('products.edit', $product) }}" class="font-medium text-[#00535b]">Edit</a>
                            <x-delete-form :action="route('products.destroy', $product)" :confirm="$product->name" />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-[#3e494a]">No products yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $products->links() }}</div>
@endsection
