@extends('layouts.app')

@section('title', 'Edit Diagnosis')
@section('page-title', 'Patient Visits')

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-semibold text-[#191c1d]">Edit Diagnosis</h1>
        <p class="mt-1 text-sm text-[#3e494a]">Update diagnosis text for this visit record.</p>
    </div>

    <form method="POST" action="{{ route('patient-visits.diagnoses.update', [$patientVisit, $diagnosis]) }}" class="max-w-2xl rounded-lg border border-[#bec8ca] bg-white p-6">
        @csrf
        @method('PUT')

        <div>
            <label class="mb-2 block text-sm font-medium">Diagnosis</label>
            <textarea name="diagnosis_text" rows="4" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]" required>{{ old('diagnosis_text', $diagnosis->diagnosis_text) }}</textarea>
            @error('diagnosis_text')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="mt-6 flex gap-3">
            <button class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">Save</button>
            <a href="{{ route('patient-visits.show', $patientVisit) }}" class="rounded-xl border border-[#bec8ca] px-4 py-2 text-sm text-[#3e494a]">Cancel</a>
        </div>
    </form>
@endsection

