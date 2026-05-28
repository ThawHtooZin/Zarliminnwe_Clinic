@extends('layouts.app')

@section('title', 'Stock Counts')
@section('page-title', 'Stock Counts')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-[#191c1d]">Stock Counts</h1>
            <p class="mt-1 text-sm text-[#3e494a]">Capture physical counts and post variance adjustments through the stock ledger.</p>
        </div>
        <a href="{{ route('stock-counts.create') }}" class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">New Stock Count</a>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-[#bec8ca] bg-white px-4 py-3 text-sm text-[#00535b]">{{ session('status') }}</div>
    @endif

    <div class="overflow-hidden rounded-lg border border-[#bec8ca] bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-[#f3f4f5] text-xs uppercase tracking-[0.06em] text-[#3e494a]">
                <tr>
                    <th class="px-5 py-3">Count Number</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3">Started</th>
                    <th class="px-5 py-3">Counted By</th>
                    <th class="px-5 py-3">Submitted</th>
                    <th class="px-5 py-3">Posted</th>
                    <th class="px-5 py-3">Lines</th>
                    <th class="px-5 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#bec8ca]">
                @forelse ($stockCounts as $stockCount)
                    <tr>
                        <td class="px-5 py-4 font-medium">{{ $stockCount->count_number }}</td>
                        <td class="px-5 py-4">{{ ucfirst($stockCount->status) }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $stockCount->started_at?->format('M d, Y H:i') ?: '-' }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $stockCount->countedBy?->name ?: '-' }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $stockCount->submitted_at?->format('M d, Y H:i') ?: '-' }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $stockCount->posted_at?->format('M d, Y H:i') ?: '-' }}</td>
                        <td class="px-5 py-4 text-[#00535b]">{{ $stockCount->lines_count }}</td>
                        <td class="px-5 py-4 text-right">
                            <a href="{{ route('stock-counts.show', $stockCount) }}" class="font-medium text-[#00535b]">
                                {{ $stockCount->isDraft() ? 'Continue' : 'View' }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-5 py-8 text-center text-[#3e494a]">No stock counts yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $stockCounts->links() }}</div>
@endsection
