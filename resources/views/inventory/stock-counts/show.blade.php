@extends('layouts.app')

@section('title', $stockCount->count_number)
@section('page-title', 'Stock Counts')

@section('content')
    @php
        $isEditable = $stockCount->isDraft();
        $canPost = $stockCount->isSubmitted() && auth()->user()->hasRole(\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_STOCK_MANAGER);
    @endphp

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-[#191c1d]">{{ $stockCount->count_number }}</h1>
            <p class="mt-1 text-sm text-[#3e494a]">
                Status: {{ ucfirst($stockCount->status) }}
                · Counted by {{ $stockCount->countedBy?->name ?: '-' }}
                · Started {{ $stockCount->started_at?->format('M d, Y H:i') ?: '-' }}
            </p>
        </div>
        <div class="flex gap-2">
            @if ($stockCount->isDraft())
                <form method="POST" action="{{ route('stock-counts.submit', $stockCount) }}">
                    @csrf
                    <button class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">Submit Count</button>
                </form>
            @endif
            @if ($canPost)
                <form method="POST" action="{{ route('stock-counts.post', $stockCount) }}">
                    @csrf
                    <button class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">Post Adjustments</button>
                </form>
            @endif
            @if (($stockCount->isDraft() || $stockCount->isSubmitted()) && auth()->user()->hasRole(\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_STOCK_MANAGER))
                <form method="POST" action="{{ route('stock-counts.cancel', $stockCount) }}">
                    @csrf
                    <button class="rounded-xl border border-red-200 px-4 py-2 text-sm font-semibold text-red-700">Cancel Count</button>
                </form>
            @endif
        </div>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-[#bec8ca] bg-white px-4 py-3 text-sm text-[#00535b]">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <section class="mb-6 grid grid-cols-4 gap-4">
        <div class="rounded-lg border border-[#bec8ca] bg-white p-4">
            <p class="text-xs uppercase tracking-[0.06em] text-[#3e494a]">Submitted</p>
            <p class="mt-2 font-medium">{{ $stockCount->submitted_at?->format('M d, Y H:i') ?: '-' }}</p>
        </div>
        <div class="rounded-lg border border-[#bec8ca] bg-white p-4">
            <p class="text-xs uppercase tracking-[0.06em] text-[#3e494a]">Posted</p>
            <p class="mt-2 font-medium">{{ $stockCount->posted_at?->format('M d, Y H:i') ?: '-' }}</p>
        </div>
        <div class="rounded-lg border border-[#bec8ca] bg-white p-4">
            <p class="text-xs uppercase tracking-[0.06em] text-[#3e494a]">Reviewed By</p>
            <p class="mt-2 font-medium">{{ $stockCount->reviewedBy?->name ?: '-' }}</p>
        </div>
        <div class="rounded-lg border border-[#bec8ca] bg-white p-4">
            <p class="text-xs uppercase tracking-[0.06em] text-[#3e494a]">Lines</p>
            <p class="mt-2 font-medium">{{ $stockCount->lines->count() }}</p>
        </div>
    </section>

    <form method="POST" action="{{ route('stock-counts.update', $stockCount) }}">
        @csrf
        @method('PUT')

        <section class="overflow-hidden rounded-lg border border-[#bec8ca] bg-white">
            <div class="flex items-center justify-between border-b border-[#bec8ca] p-5">
                <div>
                    <h2 class="text-lg font-semibold">Count Lines</h2>
                    <p class="mt-1 text-sm text-[#3e494a]">Expected quantities are snapshots from stock balances when this draft was created.</p>
                </div>
                @if (! $isEditable)
                    <span class="rounded-full bg-[#00535b08] px-3 py-1 text-xs font-medium text-[#00535b]">Read only</span>
                @endif
            </div>
            <table class="w-full min-w-[1100px] text-left text-sm">
                <thead class="bg-[#f3f4f5] text-xs uppercase tracking-[0.06em] text-[#3e494a]">
                    <tr>
                        <th class="px-5 py-3">Product</th>
                        <th class="px-5 py-3">Batch</th>
                        <th class="px-5 py-3">Unit</th>
                        <th class="px-5 py-3">Expected</th>
                        <th class="px-5 py-3">Counted</th>
                        <th class="px-5 py-3">Variance</th>
                        <th class="px-5 py-3">Notes</th>
                        <th class="px-5 py-3">Ledger</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#bec8ca]">
                    @foreach ($stockCount->lines as $index => $line)
                        <tr>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <x-product-image :product="$line->product" size="sm" />
                                    <div>
                                        <p class="font-medium text-[#191c1d]">{{ $line->product->name }}</p>
                                        <p class="text-xs text-[#3e494a]">{{ $line->product->sku }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-[#3e494a]">{{ $line->stockBatch?->batch_number ?: '-' }}</td>
                            <td class="px-5 py-4 text-[#3e494a]">{{ $line->productUnit->name }}</td>
                            <td class="px-5 py-4 text-[#00535b]">{{ rtrim(rtrim($line->expected_quantity, '0'), '.') }}</td>
                            <td class="px-5 py-4">
                                <input type="hidden" name="lines[{{ $index }}][id]" value="{{ $line->id }}">
                                <input name="lines[{{ $index }}][counted_quantity]" type="number" step="0.000001" min="0" value="{{ old("lines.$index.counted_quantity", $line->counted_quantity) }}" class="w-32 rounded-lg border border-[#bec8ca] px-3 py-2" @disabled(! $isEditable)>
                            </td>
                            <td class="px-5 py-4 font-semibold {{ (float) $line->variance_quantity < 0 ? 'text-red-700' : 'text-[#00535b]' }}">
                                {{ rtrim(rtrim($line->variance_quantity, '0'), '.') }}
                            </td>
                            <td class="px-5 py-4">
                                <input name="lines[{{ $index }}][notes]" value="{{ old("lines.$index.notes", $line->notes) }}" class="w-48 rounded-lg border border-[#bec8ca] px-3 py-2" @disabled(! $isEditable)>
                            </td>
                            <td class="px-5 py-4 text-[#3e494a]">{{ $line->adjustment_ledger_id ? '#'.$line->adjustment_ledger_id : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        @if ($isEditable)
            <div class="mt-4 flex gap-3">
                <button class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">Save Count</button>
                <a href="{{ route('stock-counts.index') }}" class="rounded-xl border border-[#bec8ca] px-4 py-2 text-sm text-[#3e494a]">Back</a>
            </div>
        @else
            <div class="mt-4">
                <a href="{{ route('stock-counts.index') }}" class="rounded-xl border border-[#bec8ca] px-4 py-2 text-sm text-[#3e494a]">Back</a>
            </div>
        @endif
    </form>
@endsection
