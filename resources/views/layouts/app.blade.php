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
    @php
        $navigation = [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'match' => 'dashboard', 'icon' => 'D'],
            ['label' => 'POS', 'route' => 'sales.pos', 'match' => 'sales.pos', 'icon' => 'S'],
            ['label' => 'Sales History', 'route' => 'sales.index', 'match' => 'sales.index', 'icon' => 'H'],
            ['label' => 'Products', 'route' => 'products.index', 'match' => 'products.*', 'icon' => 'P'],
            ['label' => 'Categories', 'route' => 'product-categories.index', 'match' => 'product-categories.*', 'icon' => 'C'],
            ['label' => 'Suppliers', 'route' => 'suppliers.index', 'match' => 'suppliers.*', 'icon' => 'S'],
            ['label' => 'Opening Stock', 'route' => 'opening-stock.create', 'match' => 'opening-stock.*', 'icon' => 'O'],
            ['label' => 'Purchase Receipts', 'route' => 'purchase-receipts.index', 'match' => 'purchase-receipts.*', 'icon' => 'R'],
            ['label' => 'Stock Ledger', 'route' => 'stock.ledger', 'match' => 'stock.*', 'icon' => 'L'],
            ['label' => 'Low-Stock Alerts', 'route' => 'stock-control.low-stock', 'match' => 'stock-control.low-stock', 'icon' => 'A'],
            ['label' => 'Expiry Alerts', 'route' => 'stock-control.expiry', 'match' => 'stock-control.expiry', 'icon' => 'E'],
            ['label' => 'Stock Counts', 'route' => 'stock-counts.index', 'match' => 'stock-counts.*', 'icon' => 'C'],
            ['label' => 'Stock Reports', 'route' => 'reports.stock-on-hand', 'match' => 'reports.*', 'icon' => 'R'],
        ];
    @endphp

    <aside class="fixed inset-y-0 left-0 z-30 flex w-[260px] flex-col border-r border-[#bec8ca] bg-[#f8f9fa] px-4 py-6">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-2 pb-8">
            <span class="flex h-10 w-10 items-center justify-center overflow-hidden rounded border border-[#bec8ca] bg-white">
                <img src="{{ asset('images/zlmnlogo.jpg') }}" alt="Zarli Min Nwe Clinic logo" class="h-full w-full object-cover">
            </span>
            <span class="min-w-0">
                <span class="block text-2xl font-bold leading-tight text-[#00535b]">ZLMN</span>
                <span class="block truncate text-xs font-medium tracking-[0.06em] text-[#3e494a]">Clinic Management</span>
            </span>
        </a>

        <div class="px-2 pb-6">
            <a href="{{ route('sales.pos') }}" class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#00535b] px-4 py-3 text-xs font-medium tracking-[0.06em] text-white shadow-sm">
                <span class="text-base leading-none">+</span>
                Go to POS
            </a>
        </div>

        <nav class="flex flex-1 flex-col gap-1">
            @foreach ($navigation as $item)
                @php($active = request()->routeIs($item['match']))
                <a href="{{ route($item['route']) }}" class="flex items-center gap-3 rounded px-3 py-2.5 text-sm transition {{ $active ? 'bg-[#00535b08] text-[#00535b]' : 'text-[#3e494a] hover:bg-white hover:text-[#00535b]' }}">
                    <span class="flex h-5 w-5 items-center justify-center rounded text-[11px] font-semibold {{ $active ? 'bg-[#00535b] text-white' : 'bg-[#e1e3e4] text-[#3e494a]' }}">
                        {{ $item['icon'] }}
                    </span>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="border-t border-[#bec8ca] pt-4">
            <div class="flex items-center gap-3 rounded bg-white px-3 py-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[#e1e3e4] text-sm font-semibold text-[#00535b]">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-[#191c1d]">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs capitalize text-[#3e494a]">{{ str_replace('_', ' ', auth()->user()->role) }}</p>
                </div>
            </div>
        </div>
    </aside>

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
