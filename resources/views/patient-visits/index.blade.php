@extends('layouts.app')

@section('title', 'Patient Visits')
@section('page-title', 'Patient Visits')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-[#191c1d]">Patient Visits</h1>
            <p class="mt-1 text-sm text-[#3e494a]">Record patient name, age, and visit time for service fee reference.</p>
        </div>
        <a href="{{ route('patient-visits.create') }}" class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">New Patient Visit</a>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-[#bec8ca] bg-white px-4 py-3 text-sm text-[#00535b]">{{ session('status') }}</div>
    @endif

    <form method="GET" action="{{ route('patient-visits.index') }}" class="mb-5 rounded-lg border border-[#bec8ca] bg-white p-4">
        <div class="grid gap-4 md:grid-cols-4">
            <div>
                <label class="mb-2 block text-sm font-medium">Patient Name</label>
                <input name="patient_name" value="{{ $filters['patient_name'] ?? '' }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium">Visit From</label>
                <input type="date" name="visited_from" value="{{ $filters['visited_from'] ?? '' }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium">Visit To</label>
                <input type="date" name="visited_to" value="{{ $filters['visited_to'] ?? '' }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">
            </div>
            <div class="flex items-end gap-2">
                <button class="rounded-xl bg-[#00535b] px-4 py-3 text-sm font-semibold text-white">Filter</button>
                <a href="{{ route('patient-visits.index') }}" class="rounded-xl border border-[#bec8ca] px-4 py-3 text-sm text-[#3e494a]">Reset</a>
            </div>
        </div>
    </form>

    <div class="overflow-hidden rounded-lg border border-[#bec8ca] bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-[#f3f4f5] text-xs uppercase tracking-[0.06em] text-[#3e494a]">
                <tr>
                    <th class="px-5 py-3">Patient Name</th>
                    <th class="px-5 py-3">Age</th>
                    <th class="px-5 py-3">Visit Time</th>
                    <th class="px-5 py-3">Created By</th>
                    <th class="px-5 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#bec8ca]">
                @forelse ($patientVisits as $patientVisit)
                    <tr>
                        <td class="px-5 py-4 font-medium">{{ $patientVisit->patient_name }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $patientVisit->age }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $patientVisit->visited_at->format('M d, Y H:i') }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $patientVisit->createdBy?->name ?: '-' }}</td>
                        <td class="px-5 py-4 text-right">
                            <a href="{{ route('patient-visits.show', $patientVisit) }}" class="font-medium text-[#00535b]">View</a>
                            <span class="mx-2 text-[#bec8ca]">|</span>
                            <a href="{{ route('patient-visits.edit', $patientVisit) }}" class="font-medium text-[#00535b]">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-[#3e494a]">No patient visits yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $patientVisits->links() }}</div>
@endsection
