@extends('layouts.app')

@section('title', 'New Purchase Receipt')
@section('page-title', 'Purchase Receipts')

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-semibold text-[#191c1d]">New Purchase Receipt</h1>
        <p class="mt-1 text-sm text-[#3e494a]">Record the exact unit bought, such as boxes, strips, bottles, or pills.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">Please fix the receipt and try again.</div>
    @endif

    <form method="POST" action="{{ route('purchase-receipts.store') }}" class="space-y-6">
        @csrf
        <section class="rounded-lg border border-[#bec8ca] bg-white p-6">
            <div class="grid grid-cols-3 gap-5">
                <div>
                    <label class="mb-2 block text-sm font-medium">Receipt Number</label>
                    <input name="receipt_number" value="{{ old('receipt_number', $receipt->receipt_number) }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm" required>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium">Supplier</label>
                    <select name="supplier_id" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm" required>
                        <option value="">Select supplier</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium">Received Date</label>
                    <input name="received_at" type="date" value="{{ old('received_at', $receipt->received_at) }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm" required>
                </div>
            </div>
            <div class="mt-5">
                <label class="mb-2 block text-sm font-medium">Notes</label>
                <textarea name="notes" rows="3" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm">{{ old('notes') }}</textarea>
            </div>
        </section>

        <section class="rounded-lg border border-[#bec8ca] bg-white p-6">
            <h2 class="mb-4 text-lg font-semibold">Receipt Lines</h2>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px] text-left text-sm">
                    <thead class="text-xs uppercase tracking-[0.06em] text-[#3e494a]">
                        <tr>
                            <th class="py-2">Product</th>
                            <th class="py-2">Unit Bought</th>
                            <th class="py-2">Qty</th>
                            <th class="py-2">Unit Cost</th>
                            <th class="py-2">Batch</th>
                            <th class="py-2">Expiry</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for ($index = 0; $index < 8; $index++)
                            <tr class="border-t border-[#bec8ca]">
                                <td class="py-2 pr-2">
                                    <select name="lines[{{ $index }}][product_id]" class="w-full rounded-lg border border-[#bec8ca] px-3 py-2">
                                        <option value="">Select product</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}" @selected(old("lines.$index.product_id") == $product->id)>{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="py-2 pr-2">
                                    <select name="lines[{{ $index }}][product_unit_id]" class="w-full rounded-lg border border-[#bec8ca] px-3 py-2">
                                        <option value="">Select unit</option>
                                        @foreach ($products as $product)
                                            @foreach ($product->units as $unit)
                                                <option value="{{ $unit->id }}" @selected(old("lines.$index.product_unit_id") == $unit->id)>{{ $product->name }} - {{ $unit->name }}</option>
                                            @endforeach
                                        @endforeach
                                    </select>
                                </td>
                                <td class="py-2 pr-2"><input name="lines[{{ $index }}][quantity]" type="number" step="0.000001" min="0" value="{{ old("lines.$index.quantity") }}" class="w-28 rounded-lg border border-[#bec8ca] px-3 py-2"></td>
                                <td class="py-2 pr-2"><input name="lines[{{ $index }}][unit_cost]" type="number" step="0.01" min="0" value="{{ old("lines.$index.unit_cost") }}" class="w-28 rounded-lg border border-[#bec8ca] px-3 py-2"></td>
                                <td class="py-2 pr-2"><input name="lines[{{ $index }}][batch_number]" value="{{ old("lines.$index.batch_number") }}" class="w-32 rounded-lg border border-[#bec8ca] px-3 py-2"></td>
                                <td class="py-2"><input name="lines[{{ $index }}][expires_at]" type="date" value="{{ old("lines.$index.expires_at") }}" class="w-40 rounded-lg border border-[#bec8ca] px-3 py-2"></td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </section>

        <div class="flex gap-3">
            <button class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">Save Draft</button>
            <a href="{{ route('purchase-receipts.index') }}" class="rounded-xl border border-[#bec8ca] px-4 py-2 text-sm text-[#3e494a]">Cancel</a>
        </div>
    </form>
@endsection
