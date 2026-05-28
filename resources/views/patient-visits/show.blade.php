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
            <a href="{{ route('patients.visit-records.edit', [$patientVisit->patient, $patientVisit]) }}" class="rounded-xl border border-[#bec8ca] px-4 py-2 text-sm text-[#3e494a]">Edit Visit</a>
            <a href="{{ route('patients.show', $patientVisit->patient) }}" class="rounded-xl border border-[#bec8ca] px-4 py-2 text-sm text-[#3e494a]">Patient Profile</a>
        </div>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-[#bec8ca] bg-white px-4 py-3 text-sm text-[#00535b]">{{ session('status') }}</div>
    @endif

    <div class="max-w-3xl rounded-lg border border-[#bec8ca] bg-white p-6">
        <dl class="grid gap-5 md:grid-cols-2">
            <div>
                <dt class="text-xs uppercase tracking-[0.06em] text-[#3e494a]">Patient ID</dt>
                <dd class="mt-1 text-lg font-semibold text-[#191c1d]">{{ $patientVisit->patient->patient_code }}</dd>
            </div>
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
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-[#191c1d]">Diagnoses</h2>
                <p class="mt-1 text-sm text-[#3e494a]">Stackable diagnoses in chronological order.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('patient-visits.diagnoses.store', $patientVisit) }}" class="mt-4">
            @csrf
            <label class="mb-2 block text-sm font-medium">Add Diagnosis</label>
            <textarea name="diagnosis_text" rows="3" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]" required>{{ old('diagnosis_text') }}</textarea>
            @error('diagnosis_text')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            <button class="mt-3 rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">Add Diagnosis</button>
        </form>

        @if ($patientVisit->diagnoses->isEmpty())
            <p class="mt-5 text-sm text-[#3e494a]">No diagnoses yet.</p>
        @else
            <div class="mt-5 overflow-hidden rounded-lg border border-[#bec8ca]">
                <table class="w-full text-left text-sm">
                    <thead class="bg-[#f3f4f5] text-xs uppercase tracking-[0.06em] text-[#3e494a]">
                        <tr>
                            <th class="px-4 py-2">Recorded</th>
                            <th class="px-4 py-2">Diagnosis</th>
                            <th class="px-4 py-2">By</th>
                            <th class="px-4 py-2 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#bec8ca]">
                        @foreach ($patientVisit->diagnoses->sortBy('recorded_at') as $diagnosis)
                            <tr>
                                <td class="px-4 py-3 text-[#3e494a]">{{ $diagnosis->recorded_at?->format('M d, Y H:i') }}</td>
                                <td class="px-4 py-3">{{ $diagnosis->diagnosis_text }}</td>
                                <td class="px-4 py-3">{{ $diagnosis->recordedBy?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('patient-visits.diagnoses.edit', [$patientVisit, $diagnosis]) }}" class="text-[#00535b]">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="mt-6 max-w-4xl rounded-lg border border-[#bec8ca] bg-white p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-[#191c1d]">Visit Income</h2>
                <p class="mt-1 text-sm text-[#3e494a]">Service income and pharmacy sales linked to this visit.</p>
            </div>
            <p class="text-sm font-semibold text-[#00535b]">Total: {{ number_format($visitIncomeTotal, 2) }}</p>
        </div>

        @if ($visitIncomeLines->isEmpty())
            <p class="mt-4 text-sm text-[#3e494a]">No income linked to this visit yet.</p>
        @else
            <div class="mt-4 overflow-hidden rounded-lg border border-[#bec8ca]">
                <table class="w-full text-left text-sm">
                    <thead class="bg-[#f3f4f5] text-xs uppercase tracking-[0.06em] text-[#3e494a]">
                        <tr>
                            <th class="px-4 py-2">Date</th>
                            <th class="px-4 py-2">Source</th>
                            <th class="px-4 py-2">Category</th>
                            <th class="px-4 py-2">Amount</th>
                            <th class="px-4 py-2 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#bec8ca]">
                        @foreach ($visitIncomeLines as $line)
                            <tr>
                                <td class="px-4 py-3 text-[#3e494a]">{{ $line->occurredAt->format('M d, Y H:i') }}</td>
                                <td class="px-4 py-3 text-[#3e494a]">{{ $line->isPharmacySale() ? 'Pharmacy Sale' : 'Service Income' }}</td>
                                <td class="px-4 py-3">
                                    {{ $line->categoryLabel }}
                                    @if ($line->description)
                                        <span class="block text-xs text-[#6f797a]">{{ $line->description }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-medium">{{ number_format($line->amount, 2) }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if ($line->actionRouteName)
                                        <a href="{{ route($line->actionRouteName, $line->actionRouteParameters) }}" class="text-[#00535b]">{{ $line->actionLabel }}</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
