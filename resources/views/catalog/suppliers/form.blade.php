@extends('layouts.app')

@section('title', $supplier->exists ? 'Edit Supplier' : 'New Supplier')
@section('page-title', 'Suppliers')

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-semibold text-[#191c1d]">{{ $supplier->exists ? 'Edit Supplier' : 'New Supplier' }}</h1>
        <p class="mt-1 text-sm text-[#3e494a]">Supplier name is required.</p>
    </div>

    <form method="POST" action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}" class="max-w-3xl rounded-lg border border-[#bec8ca] bg-white p-6">
        @csrf
        @if ($supplier->exists)
            @method('PUT')
        @endif

        <div class="grid grid-cols-2 gap-5">
            <div>
                <label class="mb-2 block text-sm font-medium">Name</label>
                <input name="name" value="{{ old('name', $supplier->name) }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]" required>
                @error('name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium">Phone</label>
                <input name="phone" value="{{ old('phone', $supplier->phone) }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium">Email</label>
                <input name="email" type="email" value="{{ old('email', $supplier->email) }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">
                @error('email')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <label class="mt-9 flex items-center gap-2 text-sm text-[#3e494a]">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $supplier->is_active))>
                Active
            </label>
        </div>

        <div class="mt-5">
            <label class="mb-2 block text-sm font-medium">Address</label>
            <textarea name="address" rows="4" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">{{ old('address', $supplier->address) }}</textarea>
        </div>

        <div class="mt-6 flex flex-wrap items-center gap-3">
            <button class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">Save</button>
            <a href="{{ route('suppliers.index') }}" class="rounded-xl border border-[#bec8ca] px-4 py-2 text-sm text-[#3e494a]">Cancel</a>
            @if ($supplier->exists)
                <x-delete-form :action="route('suppliers.destroy', $supplier)" :confirm="$supplier->name" class="ml-auto" />
            @endif
        </div>
    </form>
@endsection
