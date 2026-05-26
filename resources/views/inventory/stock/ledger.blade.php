@extends('layouts.app')

@section('title', 'Stock Ledger')
@section('page-title', 'Stock Ledger')

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-semibold text-[#191c1d]">Stock Ledger</h1>
        <p class="mt-1 text-sm text-[#3e494a]">Immutable stock movement history.</p>
    </div>

    <div class="overflow-hidden rounded-lg border border-[#bec8ca] bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-[#f3f4f5] text-xs uppercase tracking-[0.06em] text-[#3e494a]">
                <tr>
                    <th class="px-5 py-3">Date</th>
                    <th class="px-5 py-3">Product</th>
                    <th class="px-5 py-3">Type</th>
                    <th class="px-5 py-3">Direction</th>
                    <th class="px-5 py-3">Quantity</th>
                    <th class="px-5 py-3">User</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#bec8ca]">
                @forelse ($ledgers as $ledger)
                    <tr>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $ledger->created_at->format('M d, Y H:i') }}</td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <x-product-image :product="$ledger->product" size="sm" />
                                <span class="font-medium">{{ $ledger->product->name }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ str_replace('_', ' ', $ledger->type) }}</td>
                        <td class="px-5 py-4">{{ strtoupper($ledger->direction) }}</td>
                        <td class="px-5 py-4 text-[#00535b]">{{ rtrim(rtrim($ledger->quantity, '0'), '.') }} {{ $ledger->productUnit->abbreviation }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $ledger->creator?->name ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-[#3e494a]">No stock movements yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $ledgers->links() }}</div>
@endsection
