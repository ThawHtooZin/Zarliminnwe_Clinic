@extends('layouts.app')

@section('title', 'New Stock Count')
@section('page-title', 'Stock Counts')

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-semibold text-[#191c1d]">New Stock Count</h1>
        <p class="mt-1 text-sm text-[#3e494a]">Select current stock balance rows to copy their expected quantities into a draft count.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">Please select at least one stock balance row.</div>
    @endif

    <form method="POST" action="{{ route('stock-counts.store') }}" class="space-y-6">
        @csrf

        <section class="rounded-lg border border-[#bec8ca] bg-white p-6">
            <label class="mb-2 block text-sm font-medium">Notes</label>
            <textarea name="notes" rows="3" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm">{{ old('notes') }}</textarea>
        </section>

        <section class="overflow-hidden rounded-lg border border-[#bec8ca] bg-white">
            <div class="border-b border-[#bec8ca] p-5">
                <h2 class="text-lg font-semibold">Current Stock Balances</h2>
                <p class="mt-1 text-sm text-[#3e494a]">Expected quantity is copied from these balances when the draft is created.</p>
            </div>
            <table class="w-full text-left text-sm">
                <thead class="bg-[#f3f4f5] text-xs uppercase tracking-[0.06em] text-[#3e494a]">
                    <tr>
                        <th class="px-5 py-3">Select</th>
                        <th class="px-5 py-3">Product</th>
                        <th class="px-5 py-3">Category</th>
                        <th class="px-5 py-3">Batch</th>
                        <th class="px-5 py-3">Unit</th>
                        <th class="px-5 py-3">Expected Qty</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#bec8ca]">
                    @forelse ($balances as $balance)
                        <tr>
                            <td class="px-5 py-4">
                                <input type="checkbox" name="stock_balance_ids[]" value="{{ $balance->id }}" @checked(in_array($balance->id, old('stock_balance_ids', [])))>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <x-product-image :product="$balance->product" size="sm" />
                                    <div>
                                        <p class="font-medium text-[#191c1d]">{{ $balance->product->name }}</p>
                                        <p class="text-xs text-[#3e494a]">{{ $balance->product->sku }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-[#3e494a]">{{ $balance->product->category?->name ?: '-' }}</td>
                            <td class="px-5 py-4 text-[#3e494a]">{{ $balance->stockBatch?->batch_number ?: '-' }}</td>
                            <td class="px-5 py-4 text-[#3e494a]">{{ $balance->productUnit->name }}</td>
                            <td class="px-5 py-4 text-[#00535b]">{{ rtrim(rtrim($balance->quantity, '0'), '.') }} {{ $balance->productUnit->abbreviation }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-[#3e494a]">No stock balances available.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <div class="flex gap-3">
            <button class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">Create Draft Count</button>
            <a href="{{ route('stock-counts.index') }}" class="rounded-xl border border-[#bec8ca] px-4 py-2 text-sm text-[#3e494a]">Cancel</a>
        </div>
    </form>
@endsection
