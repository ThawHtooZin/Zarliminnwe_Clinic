@extends('layouts.app')

@section('title', 'Patient')
@section('page-title', 'Patients')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-[#191c1d]">Patient</h1>
            <p class="mt-1 text-sm text-[#3e494a]">Patient profile and visit history.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('patients.index') }}" class="rounded-xl border border-[#bec8ca] px-4 py-2 text-sm text-[#3e494a]">Back to Patients</a>
            <a href="{{ route('patients.edit', $patient) }}" class="rounded-xl border border-[#bec8ca] px-4 py-2 text-sm text-[#3e494a]">Edit Patient</a>
        </div>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-[#bec8ca] bg-white px-4 py-3 text-sm text-[#00535b]">{{ session('status') }}</div>
    @endif

    <div class="mb-6 grid gap-6 md:grid-cols-3">
        <div class="md:col-span-1 rounded-xl border border-[#bec8ca] bg-white p-6">
            <dl class="space-y-4">
                <div>
                    <dt class="text-xs uppercase tracking-[0.06em] text-[#3e494a]">Patient Code</dt>
                    <dd class="mt-1 text-lg font-semibold text-[#191c1d]">{{ $patient->patient_code }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-[0.06em] text-[#3e494a]">Name</dt>
                    <dd class="mt-1 text-lg font-semibold text-[#191c1d]">{{ $patient->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-[0.06em] text-[#3e494a]">Age</dt>
                    <dd class="mt-1 text-lg font-semibold text-[#191c1d]">{{ $patient->age }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-[0.06em] text-[#3e494a]">Address</dt>
                    <dd class="mt-1 text-sm text-[#191c1d]">{{ $patient->address }}</dd>
                </div>
            </dl>
        </div>

        <div class="md:col-span-2">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-[#191c1d]">Visit Records</h2>
                <a href="{{ route('patients.visit-records.create', $patient) }}" class="rounded-xl bg-[#00535b] px-3 py-2 text-xs font-semibold text-white">
                    New Visit
                </a>
            </div>
            <div class="overflow-hidden rounded-xl border border-[#bec8ca] bg-white">
                <table class="min-w-full divide-y divide-[#d8e0e1]">
                    <thead class="bg-[#f8f9fa]">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.08em] text-[#3e494a]">Visited At</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.08em] text-[#3e494a]">Status</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.08em] text-[#3e494a]">Created By</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#eef2f3]">
                        @forelse ($patient->visitRecords as $visit)
                            <tr
                                class="cursor-pointer transition hover:bg-[#00535b08]"
                                role="link"
                                tabindex="0"
                                onclick="window.location='{{ route('patient-visits.show', $visit) }}'"
                                onkeydown="if (event.key === 'Enter') { window.location='{{ route('patient-visits.show', $visit) }}' }"
                            >
                                <td class="px-5 py-4 text-sm text-[#191c1d]">
                                    {{ $visit->visited_at?->format('M d, Y H:i') }}
                                </td>
                                <td class="px-5 py-4 text-sm text-[#191c1d] capitalize">
                                    {{ $visit->status }}
                                </td>
                                <td class="px-5 py-4 text-sm text-[#191c1d]">
                                    {{ $visit->createdBy?->name ?? '-' }}
                                </td>
                                <td class="px-5 py-4 text-right text-sm">
                                    <a
                                        href="{{ route('patient-visits.show', $visit) }}"
                                        class="relative z-10 font-medium text-[#00535b] hover:underline"
                                        onclick="event.stopPropagation()"
                                    >View</a>
                                    <span class="px-1 text-[#bec8ca]">|</span>
                                    <a
                                        href="{{ route('patients.visit-records.edit', [$patient, $visit]) }}"
                                        class="relative z-10 font-medium text-[#00535b] hover:underline"
                                        onclick="event.stopPropagation()"
                                    >Edit</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-6 text-center text-sm text-[#3e494a]">No visit records yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

