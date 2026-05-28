@php
    /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection $lines */
    $isPaginated = $lines instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
    $rows = $isPaginated ? $lines : collect($lines);
    $showActions = $showActions ?? true;
    $emptyColspan = $showActions ? 8 : 7;
@endphp

<div class="overflow-hidden rounded-lg border border-[#bec8ca] bg-white">
    <table class="w-full text-left text-sm">
        <thead class="bg-[#f3f4f5] text-xs uppercase tracking-[0.06em] text-[#3e494a]">
            <tr>
                <th class="px-5 py-3">Date</th>
                <th class="px-5 py-3">Source</th>
                <th class="px-5 py-3">Category</th>
                <th class="px-5 py-3">Patient Visit</th>
                <th class="px-5 py-3">Amount</th>
                <th class="px-5 py-3">Payment</th>
                <th class="px-5 py-3">Recorded By</th>
                @if ($showActions)
                    <th class="px-5 py-3 text-right">Action</th>
                @endif
            </tr>
        </thead>
        <tbody class="divide-y divide-[#bec8ca]">
            @forelse ($rows as $line)
                <tr>
                    <td class="px-5 py-4 text-[#3e494a]">{{ $line->occurredAt->format('M d, Y H:i') }}</td>
                    <td class="px-5 py-4 text-[#3e494a]">
                        {{ $line->isPharmacySale() ? 'Pharmacy Sale' : 'Service Income' }}
                    </td>
                    <td class="px-5 py-4">
                        <span class="font-medium text-[#191c1d]">{{ $line->categoryLabel }}</span>
                        @if ($line->categoryType && ! $line->isPharmacySale())
                            <span class="block text-xs capitalize text-[#3e494a]">{{ $line->categoryType }}</span>
                        @endif
                        @if ($line->description)
                            <span class="block text-xs text-[#6f797a]">{{ $line->description }}</span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-[#3e494a]">
                        @if ($line->patientVisitRecord)
                            <a href="{{ route('patient-visits.show', $line->patientVisitRecord) }}" class="text-[#00535b]">
                                {{ $line->patientVisitRecord->patient->patient_code }} — {{ $line->patientVisitRecord->patient_name }}
                            </a>
                            <span class="block text-xs">{{ $line->patientVisitRecord->visited_at->format('M d, Y H:i') }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-5 py-4 font-medium text-[#191c1d]">{{ number_format($line->amount, 2) }}</td>
                    <td class="px-5 py-4 capitalize text-[#3e494a]">{{ str_replace('_', ' ', $line->paymentMethod) }}</td>
                    <td class="px-5 py-4 text-[#3e494a]">{{ $line->recordedByName ?: '—' }}</td>
                    @if ($showActions)
                        <td class="px-5 py-4 text-right">
                            @if ($line->actionRouteName)
                                <a href="{{ route($line->actionRouteName, $line->actionRouteParameters) }}" class="font-medium text-[#00535b]">
                                    {{ $line->actionLabel }}
                                </a>
                            @else
                                —
                            @endif
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $emptyColspan }}" class="px-5 py-8 text-center text-[#3e494a]">No income records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($isPaginated)
    <div class="mt-4">{{ $lines->links() }}</div>
@endif
