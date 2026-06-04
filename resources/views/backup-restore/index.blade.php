@extends('layouts.app')

@section('title', 'Backup & Restore')
@section('page-title', 'Backup & Restore')

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-semibold text-[#191c1d]">Backup & Restore</h1>
        <p class="mt-1 text-sm text-[#3e494a]">Export or import data by dataset. Export is CSV or SQL. Import accepts CSV or Excel (.xlsx).</p>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-[#bec8ca] bg-white px-4 py-3 text-sm text-[#00535b]">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="space-y-4">
        @foreach ($datasets as $key => $dataset)
            <div class="rounded-lg border border-[#bec8ca] bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-[#00535b]">{{ $dataset['label'] }}</h2>
                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <a href="{{ route('backup-restore.export.csv', $key) }}" class="rounded-lg border border-[#bec8ca] bg-white px-3 py-2 text-sm font-medium text-[#191c1d] hover:bg-[#f3f4f5]">Export CSV</a>
                    <a href="{{ route('backup-restore.export.sql', $key) }}" class="rounded-lg border border-[#bec8ca] bg-white px-3 py-2 text-sm font-medium text-[#191c1d] hover:bg-[#f3f4f5]">Export SQL</a>

                    <form method="post" action="{{ route('backup-restore.import', $key) }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-2">
                        @csrf
                        <input type="file" name="file" accept=".csv,.xlsx" required class="max-w-xs text-sm">
                        <label class="flex items-center gap-1 text-sm text-[#3e494a]">
                            <input type="checkbox" name="replace" value="1" class="rounded border-[#bec8ca]">
                            Replace
                        </label>
                        <button type="submit" class="rounded-lg bg-[#00535b] px-3 py-2 text-sm font-semibold text-white hover:bg-[#003f45]">Import</button>
                    </form>

                    <form method="post" action="{{ route('backup-restore.restore.sql', $key) }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-2">
                        @csrf
                        <input type="file" name="file" accept=".sql,.txt" required class="max-w-xs text-sm">
                        <label class="flex items-center gap-1 text-sm text-[#3e494a]">
                            <input type="checkbox" name="replace" value="1" class="rounded border-[#bec8ca]">
                            Replace
                        </label>
                        <button type="submit" class="rounded-lg border border-[#bec8ca] px-3 py-2 text-sm font-medium text-[#191c1d] hover:bg-[#f3f4f5]">Restore SQL</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-8 rounded-lg border border-red-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-semibold text-red-800">Full database</h2>
        <p class="mt-1 text-sm text-[#3e494a]">Download or restore the entire database. Restore replaces all application data.</p>
        <div class="mt-4 flex flex-wrap items-center gap-3">
            <a href="{{ route('backup-restore.database.export') }}" class="rounded-lg bg-[#00535b] px-4 py-2 text-sm font-semibold text-white hover:bg-[#003f45]">Download SQL backup</a>

            <form method="post" action="{{ route('backup-restore.database.restore') }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-2">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-[#3e494a]">SQL file</label>
                    <input type="file" name="file" accept=".sql,.txt" required class="mt-1 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-[#3e494a]">Type {{ $restorePhrase }} to confirm</label>
                    <input type="text" name="confirmation" required class="mt-1 rounded-lg border border-[#bec8ca] px-3 py-2 text-sm" autocomplete="off">
                </div>
                <input type="hidden" name="replace" value="1">
                <button type="submit" class="rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm font-semibold text-red-800 hover:bg-red-100">Restore database</button>
            </form>
        </div>
    </div>
@endsection
