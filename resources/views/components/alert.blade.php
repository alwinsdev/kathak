@props([
    'type' => 'info', // info | success | warning | error
])

@php
    $styles = [
        'info' => 'bg-blue-50 border-blue-400 text-blue-800',
        'success' => 'bg-green-50 border-green-500 text-green-800',
        'warning' => 'bg-orange-50 border-orange-400 text-orange-800',
        'error' => 'bg-red-50 border-red-500 text-red-800',
    ][$type] ?? 'bg-blue-50 border-blue-400 text-blue-800';
@endphp

<div {{ $attributes->merge(['class' => "border-l-4 rounded-md px-4 py-3 text-sm {$styles}"]) }}>
    {{ $slot }}
</div>
