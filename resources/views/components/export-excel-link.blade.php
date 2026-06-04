@props(['href'])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'rounded-xl border border-[#bec8ca] bg-white px-4 py-2 text-sm font-semibold text-[#00535b] hover:bg-gray-50']) }}>
    Export Excel
</a>
