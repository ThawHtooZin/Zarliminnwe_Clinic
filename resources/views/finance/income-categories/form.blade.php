@extends('layouts.app')

@section('title', $category->exists ? 'Edit Income Category' : 'New Income Category')
@section('page-title', 'Income Categories')

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-semibold text-[#191c1d]">{{ $category->exists ? 'Edit Income Category' : 'New Income Category' }}</h1>
        <p class="mt-1 text-sm text-[#3e494a]">Inactive categories stay in history but cannot be used for new income entries.</p>
    </div>

    <form method="POST" action="{{ $category->exists ? route('finance.income-categories.update', $category) : route('finance.income-categories.store') }}" class="max-w-2xl rounded-lg border border-[#bec8ca] bg-white p-6">
        @csrf
        @if ($category->exists)
            @method('PUT')
        @endif

        <div class="space-y-5">
            <div>
                <label class="mb-2 block text-sm font-medium">Name</label>
                <input name="name" value="{{ old('name', $category->name) }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]" required>
                @error('name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium">Type</label>
                <select name="type" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]" required>
                    <option value="service" @selected(old('type', $category->type) === 'service')>Service</option>
                    <option value="general" @selected(old('type', $category->type) === 'general')>General</option>
                </select>
                @error('type')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium">Description</label>
                <textarea name="description" rows="4" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">{{ old('description', $category->description) }}</textarea>
            </div>

            <label class="flex items-center gap-2 text-sm text-[#3e494a]">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active))>
                Active
            </label>
        </div>

        <div class="mt-6 flex flex-wrap items-center gap-3">
            <button class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">Save</button>
            <a href="{{ route('finance.income-categories.index') }}" class="rounded-xl border border-[#bec8ca] px-4 py-2 text-sm text-[#3e494a]">Cancel</a>
            @if ($category->exists)
                <x-delete-form :action="route('finance.income-categories.destroy', $category)" :confirm="$category->name" class="ml-auto" />
            @endif
        </div>
    </form>
@endsection
