@props([
    'product',
    'size' => 'md',
])

@php
    $sizeClasses = [
        'sm' => 'h-10 w-10 text-sm',
        'md' => 'h-14 w-14 text-base',
        'lg' => 'h-24 w-24 text-2xl',
        'xl' => 'h-32 w-32 text-3xl',
    ];

    $class = $sizeClasses[$size] ?? $sizeClasses['md'];
    $initial = strtoupper(substr($product->name ?? 'P', 0, 1));
@endphp

@if ($product->image_path)
    <img
        src="{{ asset('storage/'.$product->image_path) }}"
        alt="{{ $product->name }} image"
        {{ $attributes->merge(['class' => $class.' shrink-0 rounded-xl border border-[#bec8ca] bg-[#f8f9fa] object-cover']) }}
    >
@else
    <div {{ $attributes->merge(['class' => $class.' flex shrink-0 items-center justify-center rounded-xl border border-[#bec8ca] bg-[#e8f3f4] font-semibold text-[#00535b]']) }}>
        {{ $initial }}
    </div>
@endif
