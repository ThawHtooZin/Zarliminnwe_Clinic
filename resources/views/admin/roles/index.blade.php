@extends('layouts.app')

@section('title', 'Roles')
@section('page-title', 'Roles & Permissions')

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-semibold text-[#191c1d]">Roles & Permissions</h1>
        <p class="mt-1 text-sm text-[#3e494a]">Screen and route access per role. No per-button permissions.</p>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-[#bec8ca] bg-white px-4 py-3 text-sm text-[#00535b]">{{ session('status') }}</div>
    @endif

    <div class="overflow-hidden rounded-lg border border-[#bec8ca] bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-[#f3f4f5] text-xs uppercase tracking-[0.06em] text-[#3e494a]">
                <tr>
                    <th class="px-5 py-3">Role</th>
                    <th class="px-5 py-3">Users</th>
                    <th class="px-5 py-3">Type</th>
                    <th class="px-5 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#bec8ca]">
                @foreach ($roles as $role)
                    <tr>
                        <td class="px-5 py-4 font-medium text-[#191c1d]">{{ $role->name }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $role->users_count }}</td>
                        <td class="px-5 py-4 text-[#3e494a]">{{ $role->is_system ? 'System' : 'Custom' }}</td>
                        <td class="px-5 py-4 text-right">
                            <a href="{{ route('admin.roles.edit', $role) }}" class="text-sm font-medium text-[#00535b]">Manage Permissions</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
