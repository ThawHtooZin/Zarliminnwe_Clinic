<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Login') - Zarli Min Nwe Clinic</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f8f9fa] font-sans text-[#191c1d] antialiased">
    <main class="flex min-h-screen items-center justify-center px-6 py-12">
        @yield('content')
    </main>
</body>
</html>
