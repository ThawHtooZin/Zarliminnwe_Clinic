@extends('layouts.guest')

@section('title', 'Login')

@section('content')
    <section class="w-full max-w-md rounded-2xl border border-[#bec8ca] bg-white p-8 shadow-sm">
        <div class="mb-8 flex items-center gap-3">
            <span class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-lg border border-[#bec8ca] bg-white">
                <img src="{{ asset('images/zlmnlogo.jpg') }}" alt="Zarli Min Nwe Clinic logo" class="h-full w-full object-cover">
            </span>
            <span>
                <span class="block text-2xl font-bold leading-tight text-[#00535b]">ZLMN Clinic</span>
                <span class="block text-sm text-[#3e494a]">Sign in to continue</span>
            </span>
        </div>

        <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="mb-2 block text-sm font-medium text-[#191c1d]">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none transition focus:border-[#00535b] focus:ring-2 focus:ring-[#00535b]/10">
                @error('email')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="mb-2 block text-sm font-medium text-[#191c1d]">Password</label>
                <input id="password" name="password" type="password" required class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm outline-none transition focus:border-[#00535b] focus:ring-2 focus:ring-[#00535b]/10">
                @error('password')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-[#3e494a]">
                <input type="checkbox" name="remember" value="1" class="rounded border-[#bec8ca] text-[#00535b] focus:ring-[#00535b]">
                Remember me
            </label>

            <button type="submit" class="w-full rounded-xl bg-[#00535b] px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#003f45]">
                Login
            </button>
        </form>

        <p class="mt-6 rounded-xl bg-[#f8f9fa] px-4 py-3 text-sm text-[#3e494a]">
            Default admin: <span class="font-medium text-[#191c1d]">admin@zarliminnew.test</span> / <span class="font-medium text-[#191c1d]">password</span>
        </p>
    </section>
@endsection
