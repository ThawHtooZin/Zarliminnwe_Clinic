@extends('layouts.app')

@section('title', $patientVisit->exists ? 'Edit Visit' : 'New Visit')
@section('page-title', 'Patients')

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-semibold text-[#191c1d]">{{ $patientVisit->exists ? 'Edit Visit' : 'New Visit' }}</h1>
        <p class="mt-1 text-sm text-[#3e494a]">Record visit time for this patient. Name, age, and address are managed on the patient profile.</p>
    </div>

    <form method="POST" action="{{ $patientVisit->exists ? route('patients.visit-records.update', [$patient, $patientVisit]) : route('patients.visit-records.store', $patient) }}" class="max-w-2xl rounded-lg border border-[#bec8ca] bg-white p-6">
        @csrf
        @if ($patientVisit->exists)
            @method('PUT')
        @endif

        <div class="space-y-5">
            <div class="rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3">
                <p class="text-xs uppercase tracking-[0.06em] text-[#3e494a]">Patient</p>
                <p class="mt-1 text-sm font-semibold text-[#191c1d]">{{ $patient->patient_code }} — {{ $patient->name }} (Age {{ $patient->age }})</p>
                <p class="text-sm text-[#3e494a]">{{ $patient->address }}</p>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium">Visit Time</label>
                <input type="datetime-local" name="visited_at" value="{{ old('visited_at', $patientVisit->visited_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]" required>
                @error('visited_at')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">Save</button>
            <a href="{{ route('patients.show', $patient) }}" class="rounded-xl border border-[#bec8ca] px-4 py-2 text-sm text-[#3e494a]">Cancel</a>
        </div>
    </form>
@endsection
