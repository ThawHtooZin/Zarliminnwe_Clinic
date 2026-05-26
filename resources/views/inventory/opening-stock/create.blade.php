@extends('layouts.app')

@section('title', 'Opening Stock')
@section('page-title', 'Opening Stock')

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-semibold text-[#191c1d]">Post Opening Stock</h1>
        <p class="mt-1 text-sm text-[#3e494a]">Record starting stock using the exact product unit you have.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">Please fix the form and try again.</div>
    @endif

    <form method="POST" action="{{ route('opening-stock.store') }}" class="max-w-3xl rounded-lg border border-[#bec8ca] bg-white p-6">
        @csrf
        <div class="grid grid-cols-2 gap-5">
            <div>
                <label class="mb-2 block text-sm font-medium">Product</label>
                <select name="product_id" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm" required>
                    <option value="">Select product</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>{{ $product->name }} ({{ $product->sku }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium">Unit</label>
                <select name="product_unit_id" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm" required>
                    <option value="">Select unit</option>
                    @foreach ($products as $product)
                        @foreach ($product->units as $unit)
                            <option value="{{ $unit->id }}" @selected(old('product_unit_id') == $unit->id)>{{ $product->name }} - {{ $unit->name }}</option>
                        @endforeach
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium">Quantity</label>
                <input name="quantity" type="number" step="0.000001" min="0" value="{{ old('quantity') }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm" required>
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium">Batch Number</label>
                <input name="batch_number" value="{{ old('batch_number') }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm">
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium">Expiry Date</label>
                <input name="expires_at" type="date" value="{{ old('expires_at') }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm">
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium">Reason</label>
                <input name="reason" value="{{ old('reason', 'Opening stock') }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm">
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">Post Opening Stock</button>
            <a href="{{ route('stock.index') }}" class="rounded-xl border border-[#bec8ca] px-4 py-2 text-sm text-[#3e494a]">Cancel</a>
        </div>
    </form>
@endsection
