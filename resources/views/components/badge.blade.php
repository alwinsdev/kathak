@props([
    'color' => 'gray', // gray | green | orange | teal | red
])

@php
    $styles = [
        'gray' => 'bg-gray-100 text-gray-700',
        'green' => 'bg-green-50 text-green-700',
        'orange' => 'bg-orange-50 text-orange-700',
        'teal' => 'bg-teal-50 text-teal-700',
        'red' => 'bg-red-50 text-red-700',
    ][$color] ?? 'bg-gray-100 text-gray-700';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {$styles}"]) }}>
    {{ $slot }}
</span>
