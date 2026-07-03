@props([
    'title' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-gray-100 bg-white p-6 shadow-sm ring-1 ring-gray-900/[0.03]']) }}>
    @if ($title)
        <h3 class="mb-4 font-semibold text-gray-800">{{ $title }}</h3>
    @endif
    {{ $slot }}
</div>
