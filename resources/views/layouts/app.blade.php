<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') - Zarli Min Nwe Clinic</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f8f9fa] font-sans text-[#191c1d] antialiased" x-data="{ sidebarOpen: false, sidebarDesktopOpen: true }">
    @include('layouts.partials.sidebar')

    <header
        class="fixed left-0 right-0 top-0 z-20 flex h-14 items-center justify-between border-b border-[#bec8ca] bg-[#f8f9fa] px-4 sm:px-6"
        :class="(window.innerWidth >= 1400 && sidebarDesktopOpen) ? 'left-[260px]' : 'left-0'"
    >
        <div class="flex h-full items-center gap-3 sm:gap-6">
            <button
                type="button"
                class="inline-flex h-9 w-9 items-center justify-center rounded border border-[#bec8ca] bg-white text-[#00535b]"
                x-show="window.innerWidth < 1400"
                x-on:click="sidebarOpen = true"
                aria-label="Open navigation"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <button
                type="button"
                class="inline-flex h-9 w-9 items-center justify-center rounded border border-[#bec8ca] bg-white text-[#00535b]"
                x-show="window.innerWidth >= 1400"
                x-on:click="sidebarDesktopOpen = !sidebarDesktopOpen"
                :aria-label="sidebarDesktopOpen ? 'Collapse sidebar' : 'Expand sidebar'"
                :class="sidebarDesktopOpen ? 'ring-2 ring-[#00535b]/15' : ''"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
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

    <main class="min-h-screen pt-14" :class="(window.innerWidth >= 1400 && sidebarDesktopOpen) ? 'pl-[260px]' : 'pl-0'">
        <div class="p-4 sm:p-6 lg:p-8">
            @yield('content')
        </div>
    </main>
</body>
</html>
