@extends('layouts.app')

@section('title', $receipt->receipt_number)
@section('page-title', 'Purchase Receipts')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-[#191c1d]">{{ $receipt->receipt_number }}</h1>
            <p class="mt-1 text-sm text-[#3e494a]">{{ $receipt->supplier->name }} · {{ $receipt->received_at->format('M d, Y') }}</p>
        </div>
        @if (! $receipt->isPosted())
            <form method="POST" action="{{ route('purchase-receipts.post', $receipt) }}">
                @csrf
                <button class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">Post Receipt</button>
            </form>
        @endif
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-[#bec8ca] bg-white px-4 py-3 text-sm text-[#00535b]">{{ session('status') }}</div>
    @endif

    <section class="overflow-hidden rounded-lg border border-[#bec8ca] bg-white">
        <div class="flex items-center justify-between border-b border-[#bec8ca] p-5">
            <h2 class="text-lg font-semibold">Receipt Lines</h2>
            <span class="rounded-full bg-[#00535b08] px-3 py-1 text-xs font-medium text-[#00535b]">{{ ucfirst($receipt->status) }}</span>
        </div>
        <table class="w-full text-left text-sm">
            <thead class="bg-[#f3f4f5] text-xs uppercase tracking-[0.06em] text-[#3e494a]">
                <tr>
                    <th class="px-5 py-3">Product</th>
                    <th class="px-5 py-3">Unit</th>
                    <th class="px-5 py-3">Qty</th>
                    <th class="px-5 py-3">Unit Cost</th>
                    <th class="px-5 py-3">Total</th>
                    <th class="px-5 py-3">Batch / Expiry</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#bec8ca]">
                @foreach ($receipt->lines as $line)
                    <tr>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <x-product-image :product="$line->product" size="sm" />
                                <span class="font-medium">{{ $line->product->name }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $line->productUnit->name }}</td>
                        <td class="px-5 py-4">{{ rtrim(rtrim($line->quantity, '0'), '.') }}</td>
                        <td class="px-5 py-4">{{ number_format((float) $line->unit_cost, 2) }}</td>
                        <td class="px-5 py-4">{{ number_format((float) $line->total_cost, 2) }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $line->batch_number ?: '-' }} / {{ $line->expires_at?->format('M d, Y') ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
@endsection
