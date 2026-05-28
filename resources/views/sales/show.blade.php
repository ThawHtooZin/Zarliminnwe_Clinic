@extends('layouts.app')

@section('title', $sale->sale_number)
@section('page-title', 'Sales')

@section('content')
    @php
        $canVoidSale = auth()->user()?->hasRole(\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_PHARMACIST)
            && $sale->status === \App\Models\Sale::STATUS_COMPLETED;
    @endphp

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-[#191c1d]">{{ $sale->sale_number }}</h1>
            <p class="mt-1 text-sm text-[#3e494a]">
                {{ ucfirst($sale->status) }} · {{ $sale->sold_at?->format('M d, Y H:i') ?: 'Not sold yet' }}
            </p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('sales.receipt', $sale) }}" class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">View Receipt</a>
            <a href="{{ route('sales.index') }}" class="rounded-xl border border-[#bec8ca] px-4 py-2 text-sm text-[#3e494a]">Back to Sales</a>
        </div>
    </div>

    @if ($sale->status === \App\Models\Sale::STATUS_VOIDED)
        <div class="mb-6 rounded-xl border border-[#f0b4b4] bg-[#fdecec] p-4 text-sm text-[#9f1d1d]">
            This sale was voided by {{ $sale->voidedBy?->name ?: '-' }} on {{ $sale->voided_at?->format('M d, Y H:i') ?: '-' }}.
        </div>
    @endif

    @if ($errors->has('void'))
        <div class="mb-6 rounded-xl border border-[#f0b4b4] bg-[#fdecec] p-4 text-sm text-[#9f1d1d]">
            {{ $errors->first('void') }}
        </div>
    @endif

    <div class="grid grid-cols-[1fr_360px] gap-6">
        <section class="space-y-6">
            <div class="overflow-hidden rounded-lg border border-[#bec8ca] bg-white">
                <div class="border-b border-[#bec8ca] p-5">
                    <h2 class="text-lg font-semibold">Sale Lines</h2>
                </div>
                <table class="w-full text-left text-sm">
                    <thead class="bg-[#f3f4f5] text-xs uppercase tracking-[0.06em] text-[#3e494a]">
                        <tr>
                            <th class="px-5 py-3">Product</th>
                            <th class="px-5 py-3">Unit</th>
                            <th class="px-5 py-3">Qty</th>
                            <th class="px-5 py-3">Unit Price</th>
                            <th class="px-5 py-3">Discount</th>
                            <th class="px-5 py-3">Tax</th>
                            <th class="px-5 py-3 text-right">Line Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#bec8ca]">
                        @foreach ($sale->lines as $line)
                            <tr>
                                <td class="px-5 py-4 font-medium">{{ $line->product->name }}</td>
                                <td class="px-5 py-4 text-[#3e494a]">{{ $line->productUnit->name }}</td>
                                <td class="px-5 py-4">{{ rtrim(rtrim($line->quantity, '0'), '.') }}</td>
                                <td class="px-5 py-4">{{ number_format((float) $line->unit_price, 2) }}</td>
                                <td class="px-5 py-4">{{ number_format((float) $line->discount_amount, 2) }}</td>
                                <td class="px-5 py-4">{{ number_format((float) $line->tax_amount, 2) }}</td>
                                <td class="px-5 py-4 text-right font-semibold text-[#00535b]">{{ number_format((float) $line->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="overflow-hidden rounded-lg border border-[#bec8ca] bg-white">
                <div class="border-b border-[#bec8ca] p-5">
                    <h2 class="text-lg font-semibold">Stock Movements</h2>
                    <p class="mt-1 text-sm text-[#3e494a]">Ledger rows linked to this sale.</p>
                </div>
                <table class="w-full text-left text-sm">
                    <thead class="bg-[#f3f4f5] text-xs uppercase tracking-[0.06em] text-[#3e494a]">
                        <tr>
                            <th class="px-5 py-3">Date</th>
                            <th class="px-5 py-3">Product</th>
                            <th class="px-5 py-3">Type</th>
                            <th class="px-5 py-3">Direction</th>
                            <th class="px-5 py-3">Quantity</th>
                            <th class="px-5 py-3">Batch</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#bec8ca]">
                        @forelse ($stockMovements as $movement)
                            <tr>
                                <td class="px-5 py-4 text-[#3e494a]">{{ $movement->created_at->format('M d, Y H:i') }}</td>
                                <td class="px-5 py-4 font-medium">{{ $movement->product->name }}</td>
                                <td class="px-5 py-4 text-[#3e494a]">{{ str_replace('_', ' ', $movement->type) }}</td>
                                <td class="px-5 py-4">{{ strtoupper($movement->direction) }}</td>
                                <td class="px-5 py-4 text-[#00535b]">{{ rtrim(rtrim($movement->quantity, '0'), '.') }} {{ $movement->productUnit->abbreviation }}</td>
                                <td class="px-5 py-4 text-[#3e494a]">{{ $movement->stockBatch?->batch_number ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-8 text-center text-[#3e494a]">No stock movements linked.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="space-y-6">
            <section class="rounded-lg border border-[#bec8ca] bg-white p-6">
                <h2 class="mb-4 text-lg font-semibold">Payment Summary</h2>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between"><span class="text-[#3e494a]">Subtotal</span><span>{{ number_format((float) $sale->subtotal, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-[#3e494a]">Discount</span><span>{{ number_format((float) $sale->discount_total, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-[#3e494a]">Tax</span><span>{{ number_format((float) $sale->tax_total, 2) }}</span></div>
                    <div class="flex justify-between border-t border-[#bec8ca] pt-3 text-lg font-bold text-[#00535b]"><span>Grand Total</span><span>{{ number_format((float) $sale->grand_total, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-[#3e494a]">Payment Method</span><span class="capitalize">{{ $sale->payment_method }}</span></div>
                    <div class="flex justify-between"><span class="text-[#3e494a]">Amount Paid</span><span>{{ number_format((float) $sale->amount_paid, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-[#3e494a]">Change</span><span>{{ number_format((float) $sale->change_amount, 2) }}</span></div>
                </div>
            </section>

            <section class="rounded-lg border border-[#bec8ca] bg-white p-6">
                <h2 class="mb-4 text-lg font-semibold">Sale Info</h2>
                <div class="space-y-3 text-sm">
                    <div><span class="block text-[#3e494a]">Cashier</span><span>{{ $sale->cashier?->name ?: '-' }}</span></div>
                    <div><span class="block text-[#3e494a]">Patient</span><span>{{ $sale->patientVisitRecord?->patient->patient_code ? $sale->patientVisitRecord->patient->patient_code.' — '.$sale->patientVisitRecord->patient_name : 'No patient' }}</span></div>
                    <div><span class="block text-[#3e494a]">Status</span><span class="capitalize">{{ $sale->status }}</span></div>
                    @if ($sale->status === \App\Models\Sale::STATUS_VOIDED)
                        <div><span class="block text-[#3e494a]">Voided By</span><span>{{ $sale->voidedBy?->name ?: '-' }}</span></div>
                        <div><span class="block text-[#3e494a]">Voided At</span><span>{{ $sale->voided_at?->format('M d, Y H:i') ?: '-' }}</span></div>
                        <div><span class="block text-[#3e494a]">Void Reason</span><span>{{ $sale->void_reason ?: '-' }}</span></div>
                    @endif
                </div>
            </section>

            @if ($canVoidSale)
                <section class="rounded-lg border border-[#f0b4b4] bg-white p-6">
                    <h2 class="mb-2 text-lg font-semibold text-[#9f1d1d]">Void Sale</h2>
                    <p class="mb-4 text-sm text-[#3e494a]">Voiding creates reversal stock ledger entries. Original sale ledger rows remain unchanged.</p>
                    <form method="POST" action="{{ route('sales.void', $sale) }}" class="space-y-3">
                        @csrf
                        <textarea
                            name="void_reason"
                            required
                            rows="3"
                            placeholder="Required void reason"
                            class="w-full rounded-xl border border-[#bec8ca] px-4 py-3 text-sm outline-none focus:border-[#00535b]"
                        >{{ old('void_reason') }}</textarea>
                        @error('void_reason')
                            <p class="text-sm text-[#9f1d1d]">{{ $message }}</p>
                        @enderror
                        <button class="w-full rounded-xl bg-[#9f1d1d] px-4 py-3 text-sm font-semibold text-white">Void Sale</button>
                    </form>
                </section>
            @endif
        </aside>
    </div>
@endsection
