<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Error - Zarli Min Nwe Clinic</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">
    <main class="mx-auto flex min-h-screen w-full max-w-4xl items-center px-4 py-10 sm:px-6">
        <section class="w-full rounded-lg border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
            <p class="text-sm font-semibold uppercase tracking-[0.08em] text-[#00535b]">System Notice</p>
            <h1 class="mt-3 text-2xl font-bold text-[#00535b] sm:text-4xl">
                Contact the developer you have run into an error.
            </h1>
            <p class="mt-4 text-sm text-gray-600 sm:text-base">
                Something unexpected happened. Please contact your developer/support team for assistance.
            </p>
            <p class="mt-2 text-xs text-gray-500">
                Error code: {{ $statusCode ?? 500 }}
            </p>

            <div class="mt-6">
                <details class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <summary class="cursor-pointer text-sm font-medium text-[#00535b]">
                        Developer details (collapsed by default)
                    </summary>
                    <div class="mt-3 space-y-2 text-xs text-gray-700">
                        <p><span class="font-semibold">Type:</span> {{ get_class($exception) }}</p>
                        <p><span class="font-semibold">Message:</span> {{ $exception->getMessage() ?: 'No message provided.' }}</p>
                        <p><span class="font-semibold">Location:</span> {{ $exception->getFile() }}:{{ $exception->getLine() }}</p>
                        <pre class="mt-3 max-h-64 overflow-auto rounded bg-white p-3 text-[11px] leading-5 text-gray-700">{{ $exception->getTraceAsString() }}</pre>
                    </div>
                </details>
            </div>
        </section>
    </main>
</body>
</html>
