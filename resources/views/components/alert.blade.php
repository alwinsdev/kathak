@props([
    'type' => 'info', // info | success | warning | error
])

@php
    $styles = [
        'info' => 'bg-blue-50 border-blue-400 text-blue-800',
        'success' => 'bg-emerald-50 border-emerald-500 text-emerald-800',
        'warning' => 'bg-amber-50 border-amber-400 text-amber-800',
        'error' => 'bg-rose-50 border-rose-500 text-rose-800',
    ][$type] ?? 'bg-blue-50 border-blue-400 text-blue-800';
@endphp

<div {{ $attributes->merge(['class' => "border-l-4 rounded-xl px-4 py-3 text-sm {$styles}"]) }} role="status">
    {{ $slot }}
</div>
