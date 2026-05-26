@extends('layouts.app')

@section('title', $product->name)
@section('page-title', 'Products')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <x-product-image :product="$product" size="lg" />
            <div>
                <h1 class="text-3xl font-semibold text-[#191c1d]">{{ $product->name }}</h1>
                <p class="mt-1 text-sm text-[#3e494a]">{{ $product->sku }} · {{ $product->category?->name }}</p>
            </div>
        </div>
        <a href="{{ route('products.edit', $product) }}" class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">Edit Product</a>
    </div>

    <div class="grid grid-cols-[1fr_1fr] gap-6">
        <section class="rounded-lg border border-[#bec8ca] bg-white p-6">
            <h2 class="mb-4 text-lg font-semibold">Unit Levels</h2>
            <div class="space-y-3">
                @foreach ($product->units as $unit)
                    <div class="rounded-xl border border-[#bec8ca] p-4">
                        <div class="flex items-center justify-between">
                            <p class="font-medium">{{ $unit->name }} ({{ $unit->abbreviation }})</p>
                            <p class="text-sm text-[#00535b]">{{ number_format((float) $unit->sale_price, 2) }}</p>
                        </div>
                        <p class="mt-1 text-sm text-[#3e494a]">
                            Parent: {{ $unit->parent?->name ?: 'None' }}
                            @if ($unit->parent)
                                · 1 {{ $unit->parent->abbreviation }} = {{ $unit->conversion_factor }} {{ $unit->abbreviation }}
                            @endif
                        </p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-lg border border-[#bec8ca] bg-white p-6">
            <h2 class="mb-4 text-lg font-semibold">Current Stock</h2>
            <div class="space-y-3">
                @forelse ($product->stockBalances as $balance)
                    <div class="flex items-center justify-between rounded-xl border border-[#bec8ca] p-4">
                        <span>{{ $balance->stockBatch?->batch_number ?: 'No batch' }}</span>
                        <span class="font-medium">{{ rtrim(rtrim($balance->quantity, '0'), '.') }} {{ $balance->productUnit->abbreviation }}</span>
                    </div>
                @empty
                    <p class="text-sm text-[#3e494a]">No stock posted yet.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
