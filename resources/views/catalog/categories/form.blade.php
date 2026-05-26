@extends('layouts.app')

@section('title', $category->exists ? 'Edit Category' : 'New Category')
@section('page-title', 'Categories')

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-semibold text-[#191c1d]">{{ $category->exists ? 'Edit Category' : 'New Category' }}</h1>
        <p class="mt-1 text-sm text-[#3e494a]">Category name is required.</p>
    </div>

    <form method="POST" action="{{ $category->exists ? route('product-categories.update', $category) : route('product-categories.store') }}" class="max-w-2xl rounded-lg border border-[#bec8ca] bg-white p-6">
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
                <label class="mb-2 block text-sm font-medium">Description</label>
                <textarea name="description" rows="4" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">{{ old('description', $category->description) }}</textarea>
            </div>

            <label class="flex items-center gap-2 text-sm text-[#3e494a]">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active))>
                Active
            </label>
        </div>

        <div class="mt-6 flex gap-3">
            <button class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">Save</button>
            <a href="{{ route('product-categories.index') }}" class="rounded-xl border border-[#bec8ca] px-4 py-2 text-sm text-[#3e494a]">Cancel</a>
        </div>
    </form>
@endsection
