@extends('layouts.app')

@section('title', 'Patients')
@section('page-title', 'Patients')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-[#191c1d]">Patients</h1>
            <p class="mt-1 text-sm text-[#3e494a]">Search by patient code or name, then open the patient profile.</p>
        </div>
        <a href="{{ route('patients.create') }}" class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">
            New Patient
        </a>
    </div>

    <form method="GET" action="{{ route('patients.index') }}" class="mb-4 rounded-xl border border-[#bec8ca] bg-white p-4">
        <div class="grid gap-4 md:grid-cols-3">
            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.08em] text-[#3e494a]">Patient Code</label>
                <input name="patient_code" value="{{ $filters['patient_code'] ?? '' }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-3 py-2 text-sm outline-none focus:border-[#00535b]">
            </div>
            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.08em] text-[#3e494a]">Name</label>
                <input name="name" value="{{ $filters['name'] ?? '' }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-3 py-2 text-sm outline-none focus:border-[#00535b]">
            </div>
            <div class="flex items-end">
                <button class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">Filter</button>
            </div>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl border border-[#bec8ca] bg-white">
        <table class="min-w-full divide-y divide-[#d8e0e1]">
            <thead class="bg-[#f8f9fa]">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.08em] text-[#3e494a]">Patient Code</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.08em] text-[#3e494a]">Name</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.08em] text-[#3e494a]">Age</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.08em] text-[#3e494a]">Address</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#eef2f3]">
                @forelse ($patients as $patient)
                    <tr
                        class="cursor-pointer transition hover:bg-[#00535b08]"
                        role="link"
                        tabindex="0"
                        onclick="window.location='{{ route('patients.show', $patient) }}'"
                        onkeydown="if (event.key === 'Enter') { window.location='{{ route('patients.show', $patient) }}' }"
                    >
                        <td class="px-5 py-4 text-sm text-[#191c1d]">{{ $patient->patient_code }}</td>
                        <td class="px-5 py-4 text-sm text-[#191c1d]">{{ $patient->name }}</td>
                        <td class="px-5 py-4 text-sm text-[#191c1d]">{{ $patient->age }}</td>
                        <td class="px-5 py-4 text-sm text-[#191c1d]">{{ $patient->address }}</td>
                        <td class="px-5 py-4 text-right text-sm">
                            <a
                                href="{{ route('patients.show', $patient) }}"
                                class="relative z-10 font-medium text-[#00535b] hover:underline"
                                onclick="event.stopPropagation()"
                            >View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-6 text-center text-sm text-[#3e494a]">No patients found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-[#d8e0e1] bg-[#f8f9fa] px-5 py-3">
            {{ $patients->links() }}
        </div>
    </div>
@endsection

