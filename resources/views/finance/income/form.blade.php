@extends('layouts.app')

@section('title', $incomeEntry->exists ? 'Edit Income' : 'Record Income')
@section('page-title', 'Income')

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-semibold text-[#191c1d]">{{ $incomeEntry->exists ? 'Edit Income Entry' : 'Record Income Entry' }}</h1>
        <p class="mt-1 text-sm text-[#3e494a]">Patient visit is optional. Pharmacy POS sales stay in Sales and are not copied here.</p>
    </div>

    @error('form')
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
    @enderror

    <form method="POST" action="{{ $incomeEntry->exists ? route('finance.income.update', $incomeEntry) : route('finance.income.store') }}" class="max-w-2xl rounded-lg border border-[#bec8ca] bg-white p-6">
        @csrf
        @if ($incomeEntry->exists)
            @method('PUT')
        @endif

        <div class="space-y-5">
            <div>
                <label class="mb-2 block text-sm font-medium">Income Category</label>
                <select name="income_category_id" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]" required>
                    <option value="">Select category</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('income_category_id', $incomeEntry->income_category_id) == $category->id)>
                            {{ $category->name }} ({{ $category->type }})
                        </option>
                    @endforeach
                </select>
                @error('income_category_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium">Patient Visit <span class="font-normal text-[#3e494a]">(optional)</span></label>
                <select name="patient_visit_id" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">
                    <option value="">No patient visit</option>
                    @foreach ($patientVisits as $visit)
                        <option value="{{ $visit->id }}" @selected(old('patient_visit_id', $incomeEntry->patient_visit_id) == $visit->id)>
                            {{ $visit->patient_name }} — Age {{ $visit->age }} — {{ $visit->visited_at->format('M d, Y H:i') }}
                        </option>
                    @endforeach
                </select>
                @error('patient_visit_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium">Amount</label>
                <input type="number" name="amount" value="{{ old('amount', $incomeEntry->amount) }}" min="0.01" step="0.01" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]" required>
                @error('amount')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium">Payment Method</label>
                <select name="payment_method" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]" required>
                    @foreach (\App\Models\IncomeEntry::paymentMethods() as $method)
                        <option value="{{ $method }}" @selected(old('payment_method', $incomeEntry->payment_method) === $method)>
                            {{ ucfirst(str_replace('_', ' ', $method)) }}
                        </option>
                    @endforeach
                </select>
                @error('payment_method')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium">Received At</label>
                <input type="datetime-local" name="received_at" value="{{ old('received_at', $incomeEntry->received_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]" required>
                @error('received_at')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium">Description</label>
                <textarea name="description" rows="3" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">{{ old('description', $incomeEntry->description) }}</textarea>
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">Save</button>
            <a href="{{ route('finance.income.index') }}" class="rounded-xl border border-[#bec8ca] px-4 py-2 text-sm text-[#3e494a]">Cancel</a>
        </div>
    </form>
@endsection
