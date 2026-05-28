@extends('layouts.app')

@section('title', $user->exists ? 'Edit User' : 'New User')
@section('page-title', 'Users')

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-semibold text-[#191c1d]">{{ $user->exists ? 'Edit User' : 'New User' }}</h1>
        <p class="mt-1 text-sm text-[#3e494a]">Assign a role to control screen and route access.</p>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-[#bec8ca] bg-white px-4 py-3 text-sm text-[#00535b]">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="mb-8 max-w-2xl rounded-lg border border-[#bec8ca] bg-white p-6">
        @csrf
        @if ($user->exists)
            @method('PUT')
        @endif

        <div class="space-y-5">
            <div>
                <label class="mb-2 block text-sm font-medium">Name</label>
                <input name="name" value="{{ old('name', $user->name) }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]" required>
                @error('name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]" required>
                @error('email')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium">Role</label>
                <select name="role_id" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]" required>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id) == $role->id)>{{ $role->name }}</option>
                    @endforeach
                </select>
                @error('role_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-[#3e494a]">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true))>
                Active
            </label>

            <div class="border-t border-[#bec8ca] pt-5">
                <p class="mb-3 text-sm font-medium text-[#191c1d]">{{ $user->exists ? 'Change Password (optional)' : 'Password' }}</p>
                <div class="space-y-4">
                    <div>
                        <label class="mb-2 block text-sm text-[#3e494a]">Password</label>
                        <input type="password" name="password" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]" @required(! $user->exists)>
                        @error('password')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-[#3e494a]">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]">
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">Save</button>
            <a href="{{ route('admin.users.index') }}" class="rounded-xl border border-[#bec8ca] px-4 py-2 text-sm text-[#3e494a]">Cancel</a>
        </div>
    </form>

    @if ($user->exists)
        <div class="max-w-2xl rounded-lg border border-[#bec8ca] bg-white p-6">
            <h2 class="text-lg font-semibold text-[#191c1d]">Reset Password</h2>
            <p class="mt-1 text-sm text-[#3e494a]">Set a new password immediately for this user.</p>

            <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label class="mb-2 block text-sm text-[#3e494a]">New Password</label>
                    <input type="password" name="password" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]" required>
                </div>
                <div>
                    <label class="mb-2 block text-sm text-[#3e494a]">Confirm New Password</label>
                    <input type="password" name="password_confirmation" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none focus:border-[#00535b]" required>
                </div>
                <button class="rounded-xl border border-[#00535b] px-4 py-2 text-sm font-semibold text-[#00535b]">Reset Password</button>
            </form>
        </div>
    @endif
@endsection
