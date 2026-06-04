@extends('layouts.app')

@section('title', 'Suppliers')
@section('page-title', 'Suppliers')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-[#191c1d]">Suppliers</h1>
            <p class="mt-1 text-sm text-[#3e494a]">Manage pharmacy stock suppliers.</p>
        </div>
        <a href="{{ route('suppliers.create') }}" class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">New Supplier</a>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-[#bec8ca] bg-white px-4 py-3 text-sm text-[#00535b]">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="overflow-hidden rounded-lg border border-[#bec8ca] bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-[#f3f4f5] text-xs uppercase tracking-[0.06em] text-[#3e494a]">
                <tr>
                    <th class="px-5 py-3">Name</th>
                    <th class="px-5 py-3">Phone</th>
                    <th class="px-5 py-3">Email</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#bec8ca]">
                @forelse ($suppliers as $supplier)
                    <tr>
                        <td class="px-5 py-4 font-medium">{{ $supplier->name }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $supplier->phone ?: '-' }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $supplier->email ?: '-' }}</td>
                        <td class="px-5 py-4">{{ $supplier->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="px-5 py-4 text-right space-x-3">
                            <a href="{{ route('suppliers.edit', $supplier) }}" class="font-medium text-[#00535b]">Edit</a>
                            <x-delete-form :action="route('suppliers.destroy', $supplier)" :confirm="$supplier->name" />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-[#3e494a]">No suppliers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $suppliers->links() }}</div>
@endsection
