<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') - Zarli Min Nwe Clinic</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f8f9fa] font-sans text-[#191c1d] antialiased">
    @include('layouts.partials.sidebar')

    <header class="fixed left-[260px] right-0 top-0 z-20 flex h-14 items-center justify-between border-b border-[#bec8ca] bg-[#f8f9fa] px-6">
        <div class="flex h-full items-center gap-6">
            <div class="flex h-full items-center border-b-2 border-[#00535b] text-xl font-bold text-[#00535b]">
                @yield('page-title', 'Dashboard')
            </div>
        </div>

        <div class="flex items-center gap-4">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-xl px-3 py-1.5 text-xs font-medium tracking-[0.06em] text-[#3e494a] hover:text-[#00535b]">
                    Logout
                </button>
            </form>
        </div>
    </header>

    <main class="min-h-screen pl-[260px] pt-14">
        <div class="p-8">
            @yield('content')
        </div>
    </main>
</body>
</html>
