@extends('layouts.app')

@section('title', 'Stock Adjustment')
@section('page-title', 'Stock Control')

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-semibold text-[#191c1d]">Expired Stock Adjustment</h1>
        <p class="mt-1 text-sm text-[#3e494a]">Post an explicit adjustment to remove expired or unusable stock from the selected batch.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('stock-adjustments.store') }}" class="space-y-6">
        @csrf
        <input type="hidden" name="stock_balance_id" value="{{ $balance->id }}">

        <section class="grid grid-cols-4 gap-4 rounded-lg border border-[#bec8ca] bg-white p-6">
            <div>
                <p class="text-xs uppercase tracking-[0.06em] text-[#3e494a]">Product</p>
                <p class="mt-2 font-medium">{{ $balance->product->name }}</p>
                <p class="text-xs text-[#3e494a]">{{ $balance->product->sku }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-[0.06em] text-[#3e494a]">Batch</p>
                <p class="mt-2 font-medium">{{ $balance->stockBatch?->batch_number ?: '-' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-[0.06em] text-[#3e494a]">Unit</p>
                <p class="mt-2 font-medium">{{ $balance->productUnit->name }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-[0.06em] text-[#3e494a]">Remaining</p>
                <p class="mt-2 font-medium text-[#00535b]">{{ rtrim(rtrim($balance->quantity, '0'), '.') }} {{ $balance->productUnit->abbreviation }}</p>
            </div>
        </section>

        <section class="rounded-lg border border-[#bec8ca] bg-white p-6">
            <div class="grid grid-cols-2 gap-5">
                <div>
                    <label class="mb-2 block text-sm font-medium">Quantity To Remove</label>
                    <input name="quantity" type="number" step="0.000001" min="0.000001" max="{{ $balance->quantity }}" value="{{ old('quantity', $balance->quantity) }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm" required>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium">Direction</label>
                    <input value="OUT" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm" disabled>
                </div>
            </div>
            <div class="mt-5">
                <label class="mb-2 block text-sm font-medium">Reason</label>
                <textarea name="reason" rows="3" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm" required>{{ old('reason', 'Expired stock removal') }}</textarea>
            </div>
        </section>

        <div class="flex gap-3">
            <button class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">Post Adjustment</button>
            <a href="{{ route('stock-control.expiry') }}" class="rounded-xl border border-[#bec8ca] px-4 py-2 text-sm text-[#3e494a]">Cancel</a>
        </div>
    </form>
@endsection
