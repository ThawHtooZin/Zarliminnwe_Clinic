@extends('layouts.app')

@section('title', 'Purchase Receipts')
@section('page-title', 'Purchase Receipts')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-[#191c1d]">Purchase Receipts</h1>
            <p class="mt-1 text-sm text-[#3e494a]">Receive stock from suppliers in real-world units.</p>
        </div>
        <a href="{{ route('purchase-receipts.create') }}" class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">New Receipt</a>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-[#bec8ca] bg-white px-4 py-3 text-sm text-[#00535b]">{{ session('status') }}</div>
    @endif

    <div class="overflow-hidden rounded-lg border border-[#bec8ca] bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-[#f3f4f5] text-xs uppercase tracking-[0.06em] text-[#3e494a]">
                <tr>
                    <th class="px-5 py-3">Receipt</th>
                    <th class="px-5 py-3">Supplier</th>
                    <th class="px-5 py-3">Received</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#bec8ca]">
                @forelse ($receipts as $receipt)
                    <tr>
                        <td class="px-5 py-4 font-medium">{{ $receipt->receipt_number }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $receipt->supplier->name }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $receipt->received_at->format('M d, Y') }}</td>
                        <td class="px-5 py-4">{{ ucfirst($receipt->status) }}</td>
                        <td class="px-5 py-4 text-right">
                            <a href="{{ route('purchase-receipts.show', $receipt) }}" class="font-medium text-[#00535b]">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-[#3e494a]">No purchase receipts yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $receipts->links() }}</div>
@endsection
