@extends('layouts.app')

@section('title', $patientVisit->exists ? 'Edit Patient Visit' : 'New Patient Visit')
@section('page-title', 'Patient Visits')

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-semibold text-[#191c1d]">{{ $patientVisit->exists ? 'Edit Patient Visit' : 'New Patient Visit' }}</h1>
        <p class="mt-1 text-sm text-[#3e494a]">Only patient name, age, and visit time are recorded.</p>
    </div>

    <form method="POST" action="{{ $patientVisit->exists ? route('patient-visits.update', $patientVisit) : route('patient-visits.store') }}" class="max-w-2xl rounded-lg border border-[#bec8ca] bg-white p-6">
        @csrf
        @if ($patientVisit->exists)
            @method('PUT')
        @endif

        <div class="space-y-5">
            <div>
                <label class="mb-2 block text-sm font-medium">Patient Name</label>
                <input name="patient_name" value="{{ old('patient_name', $patientVisit->patient_name) }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]" required>
                @error('patient_name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium">Age</label>
                <input type="number" name="age" value="{{ old('age', $patientVisit->age) }}" min="0" max="150" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]" required>
                @error('age')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium">Visit Time</label>
                <input type="datetime-local" name="visited_at" value="{{ old('visited_at', $patientVisit->visited_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]" required>
                @error('visited_at')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">Save</button>
            <a href="{{ route('patient-visits.index') }}" class="rounded-xl border border-[#bec8ca] px-4 py-2 text-sm text-[#3e494a]">Cancel</a>
        </div>
    </form>
@endsection
