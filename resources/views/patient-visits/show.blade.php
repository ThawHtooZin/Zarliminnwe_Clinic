@extends('layouts.app')

@section('title', 'Patient Visit')
@section('page-title', 'Patient Visits')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-[#191c1d]">Patient Visit</h1>
            <p class="mt-1 text-sm text-[#3e494a]">Service fee reference record.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('finance.income.create', ['patient_visit_id' => $patientVisit->id]) }}" class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">Record Service Income</a>
            <a href="{{ route('patient-visits.edit', $patientVisit) }}" class="rounded-xl border border-[#bec8ca] px-4 py-2 text-sm text-[#3e494a]">Edit</a>
            <a href="{{ route('patient-visits.index') }}" class="rounded-xl border border-[#bec8ca] px-4 py-2 text-sm text-[#3e494a]">Back</a>
        </div>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-[#bec8ca] bg-white px-4 py-3 text-sm text-[#00535b]">{{ session('status') }}</div>
    @endif

    <div class="max-w-3xl rounded-lg border border-[#bec8ca] bg-white p-6">
        <dl class="grid gap-5 md:grid-cols-2">
            <div>
                <dt class="text-xs uppercase tracking-[0.06em] text-[#3e494a]">Patient Name</dt>
                <dd class="mt-1 text-lg font-semibold text-[#191c1d]">{{ $patientVisit->patient_name }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-[0.06em] text-[#3e494a]">Age</dt>
                <dd class="mt-1 text-lg font-semibold text-[#191c1d]">{{ $patientVisit->age }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-[0.06em] text-[#3e494a]">Visit Time</dt>
                <dd class="mt-1 text-lg font-semibold text-[#191c1d]">{{ $patientVisit->visited_at->format('M d, Y H:i') }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-[0.06em] text-[#3e494a]">Created By</dt>
                <dd class="mt-1 text-lg font-semibold text-[#191c1d]">{{ $patientVisit->createdBy?->name ?: '-' }}</dd>
            </div>
        </dl>
    </div>

    <div class="mt-6 max-w-3xl rounded-lg border border-[#bec8ca] bg-white p-6">
        <h2 class="text-lg font-semibold text-[#191c1d]">Linked Income</h2>
        <p class="mt-1 text-sm text-[#3e494a]">Service fees recorded for this visit.</p>

        @if ($patientVisit->incomeEntries->isEmpty())
            <p class="mt-4 text-sm text-[#3e494a]">No income entries linked yet.</p>
        @else
            <div class="mt-4 overflow-hidden rounded-lg border border-[#bec8ca]">
                <table class="w-full text-left text-sm">
                    <thead class="bg-[#f3f4f5] text-xs uppercase tracking-[0.06em] text-[#3e494a]">
                        <tr>
                            <th class="px-4 py-2">Received</th>
                            <th class="px-4 py-2">Category</th>
                            <th class="px-4 py-2">Amount</th>
                            <th class="px-4 py-2 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#bec8ca]">
                        @foreach ($patientVisit->incomeEntries as $entry)
                            <tr>
                                <td class="px-4 py-3 text-[#3e494a]">{{ $entry->received_at->format('M d, Y H:i') }}</td>
                                <td class="px-4 py-3">{{ $entry->incomeCategory->name }}</td>
                                <td class="px-4 py-3 font-medium">{{ number_format($entry->amount, 2) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('finance.income.edit', $entry) }}" class="text-[#00535b]">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
