@extends('layouts.app')

@section('title', $title)
@section('page-title', $title)

@section('content')
    <section class="rounded-lg border border-[#bec8ca] bg-white p-8">
        <p class="text-xs font-medium tracking-[0.06em] text-[#00535b]">PHASE 1 MODULE</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-[-0.01em] text-[#191c1d]">{{ $title }}</h1>
        <p class="mt-3 max-w-2xl text-sm leading-6 text-[#3e494a]">
            This page is connected to the master dashboard layout and protected by authentication.
            The detailed {{ strtolower($title) }} workflow will be implemented in its Phase 1 task.
        </p>
    </section>
@endsection
