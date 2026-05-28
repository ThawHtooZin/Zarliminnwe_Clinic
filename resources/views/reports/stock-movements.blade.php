@extends('layouts.app')

@section('title', 'Stock Movement Report')
@section('page-title', 'Stock Reports')

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-semibold text-[#191c1d]">Stock Movement Report</h1>
        <p class="mt-1 text-sm text-[#3e494a]">Traceable stock ledger movement history.</p>
    </div>

    <form class="mb-4 grid grid-cols-6 gap-3 rounded-lg border border-[#bec8ca] bg-white p-4">
        <input name="date_from" type="date" value="{{ $filters['date_from'] }}" class="rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm">
        <input name="date_to" type="date" value="{{ $filters['date_to'] }}" class="rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm">
        <select name="product_id" class="rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm">
            <option value="">All products</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}" @selected((int) ($filters['product_id'] ?? 0) === $product->id)>{{ $product->name }}</option>
            @endforeach
        </select>
        <select name="type" class="rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm">
            <option value="">All types</option>
            @foreach ($types as $type)
                <option value="{{ $type }}" @selected($filters['type'] === $type)>{{ str_replace('_', ' ', $type) }}</option>
            @endforeach
        </select>
        <select name="direction" class="rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm">
            <option value="">All directions</option>
            @foreach ($directions as $direction)
                <option value="{{ $direction }}" @selected($filters['direction'] === $direction)>{{ strtoupper($direction) }}</option>
            @endforeach
        </select>
        <button class="rounded-xl bg-[#00535b] px-4 py-3 text-sm font-semibold text-white">Filter</button>
    </form>

    <div class="overflow-hidden rounded-lg border border-[#bec8ca] bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-[#f3f4f5] text-xs uppercase tracking-[0.06em] text-[#3e494a]">
                <tr>
                    <th class="px-5 py-3">Date</th>
                    <th class="px-5 py-3">Product</th>
                    <th class="px-5 py-3">Batch</th>
                    <th class="px-5 py-3">Unit</th>
                    <th class="px-5 py-3">Type</th>
                    <th class="px-5 py-3">Direction</th>
                    <th class="px-5 py-3">Quantity</th>
                    <th class="px-5 py-3">Reference</th>
                    <th class="px-5 py-3">Created By</th>
                    <th class="px-5 py-3">Reason</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#bec8ca]">
                @forelse ($ledgers as $ledger)
                    <tr>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $ledger->created_at->format('M d, Y H:i') }}</td>
                        <td class="px-5 py-4 font-medium">{{ $ledger->product->name }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $ledger->stockBatch?->batch_number ?: '-' }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $ledger->productUnit->name }}</td>
                        <td class="px-5 py-4">{{ str_replace('_', ' ', $ledger->type) }}</td>
                        <td class="px-5 py-4">{{ strtoupper($ledger->direction) }}</td>
                        <td class="px-5 py-4 text-[#00535b]">{{ rtrim(rtrim($ledger->quantity, '0'), '.') }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ class_basename($ledger->reference_type) ?: '-' }} {{ $ledger->reference_id ?: '' }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $ledger->creator?->name ?: '-' }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $ledger->reason ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="px-5 py-8 text-center text-[#3e494a]">No stock movements found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $ledgers->links() }}</div>
@endsection
