@props([
    'title' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-gray-200 bg-white p-6 shadow-sm']) }}>
    @if ($title)
        <h3 class="mb-4 font-semibold text-gray-800">{{ $title }}</h3>
    @endif
    {{ $slot }}
</div>
