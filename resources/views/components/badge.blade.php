@props([
    'color' => 'gray', // gray | green | orange | teal | red
])

@php
    $styles = [
        'gray' => 'bg-gray-100 text-gray-700',
        'green' => 'bg-emerald-50 text-emerald-700',
        'orange' => 'bg-amber-50 text-amber-700',
        'teal' => 'bg-teal-50 text-teal-700',
        'red' => 'bg-rose-50 text-rose-700',
    ][$color] ?? 'bg-gray-100 text-gray-700';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {$styles}"]) }}>
    {{ $slot }}
</span>
