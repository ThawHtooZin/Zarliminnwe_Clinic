@extends('layouts.app')

@section('title', 'Expense Categories')
@section('page-title', 'Expense Categories')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-[#191c1d]">Expense Categories</h1>
            <p class="mt-1 text-sm text-[#3e494a]">Group clinic and pharmacy expenses.</p>
        </div>
        <a href="{{ route('finance.expense-categories.create') }}" class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">New Expense Category</a>
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
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#bec8ca]">
                @forelse ($categories as $category)
                    <tr>
                        <td class="px-5 py-4 font-medium text-[#191c1d]">{{ $category->name }}</td>
                        <td class="px-5 py-4">
                            <span class="rounded-full px-3 py-1 text-xs {{ $category->is_active ? 'bg-[#00535b08] text-[#00535b]' : 'bg-[#e1e3e4] text-[#3e494a]' }}">
                                {{ $category->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right space-x-3">
                            <a href="{{ route('finance.expense-categories.edit', $category) }}" class="text-sm font-medium text-[#00535b]">Edit</a>
                            <x-delete-form :action="route('finance.expense-categories.destroy', $category)" :confirm="$category->name" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-5 py-8 text-center text-[#3e494a]">No expense categories yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $categories->links() }}</div>
@endsection
