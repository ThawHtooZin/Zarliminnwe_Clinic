@extends('layouts.app')

@section('title', 'Sales')
@section('page-title', 'Sales')

@section('content')
    @php
        $canVoidSales = auth()->user()?->hasRole(\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_PHARMACIST);
    @endphp

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-[#191c1d]">Sales History</h1>
            <p class="mt-1 text-sm text-[#3e494a]">Review completed, held, and voided pharmacy sales.</p>
        </div>
        <a href="{{ route('sales.pos') }}" class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">Open POS</a>
    </div>

    <form class="mb-4 grid max-w-3xl grid-cols-[1fr_1fr_auto] gap-3">
        <select name="status" class="rounded-xl border border-[#bec8ca] bg-white px-4 py-3 text-sm outline-none focus:border-[#00535b]">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <input name="date" type="date" value="{{ request('date') }}" class="rounded-xl border border-[#bec8ca] bg-white px-4 py-3 text-sm outline-none focus:border-[#00535b]">
        <button class="rounded-xl bg-[#00535b] px-4 py-3 text-sm font-semibold text-white">Filter</button>
    </form>

    <div class="overflow-hidden rounded-lg border border-[#bec8ca] bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-[#f3f4f5] text-xs uppercase tracking-[0.06em] text-[#3e494a]">
                <tr>
                    <th class="px-5 py-3">Sale Number</th>
                    <th class="px-5 py-3">Sold Date/Time</th>
                    <th class="px-5 py-3">Cashier</th>
                    <th class="px-5 py-3">Patient</th>
                    <th class="px-5 py-3">Total</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#bec8ca]">
                @forelse ($sales as $sale)
                    @php
                        $statusClasses = match ($sale->status) {
                            \App\Models\Sale::STATUS_VOIDED => 'bg-[#fdecec] text-[#9f1d1d]',
                            \App\Models\Sale::STATUS_HELD => 'bg-[#fff7db] text-[#7a4b00]',
                            default => 'bg-[#00535b08] text-[#00535b]',
                        };
                    @endphp
                    <tr>
                        <td class="px-5 py-4 font-medium text-[#191c1d]">{{ $sale->sale_number }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $sale->sold_at?->format('M d, Y H:i') ?: '-' }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $sale->cashier?->name ?: '-' }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $sale->patient_visit_id ?: 'No patient' }}</td>
                        <td class="px-5 py-4 font-semibold text-[#00535b]">{{ number_format((float) $sale->grand_total, 2) }}</td>
                        <td class="px-5 py-4">
                            <span class="rounded-full px-3 py-1 text-xs font-medium uppercase {{ $statusClasses }}">{{ $sale->status }}</span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            @if ($sale->status === \App\Models\Sale::STATUS_HELD)
                                <a href="{{ route('sales.resume', $sale) }}" class="mr-3 font-medium text-[#00535b]">Resume</a>
                            @endif
                            <a href="{{ route('sales.show', $sale) }}" class="mr-3 font-medium text-[#00535b]">Details</a>
                            <a href="{{ route('sales.receipt', $sale) }}" class="font-medium text-[#00535b]">Receipt</a>
                            @if ($canVoidSales && $sale->status === \App\Models\Sale::STATUS_COMPLETED)
                                <form method="POST" action="{{ route('sales.void', $sale) }}" class="mt-3 flex justify-end gap-2">
                                    @csrf
                                    <input
                                        name="void_reason"
                                        required
                                        placeholder="Void reason"
                                        class="w-40 rounded-lg border border-[#bec8ca] px-3 py-2 text-xs outline-none focus:border-[#00535b]"
                                    >
                                    <button class="rounded-lg bg-[#9f1d1d] px-3 py-2 text-xs font-semibold text-white">Void</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-8 text-center text-[#3e494a]">No sales found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $sales->links() }}</div>
@endsection
