@props([
    'label' => '',
    'value' => '',
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'bg-white border border-gray-200 rounded-xl p-5 shadow-sm']) }}>
    <div class="flex items-start justify-between">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $label }}</div>
            <div class="mt-2 text-2xl font-bold text-gray-900">{{ $value }}</div>
        </div>
        @if ($icon)
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-50 text-lg">{{ $icon }}</div>
        @endif
    </div>

    @isset($footer)
        <div class="mt-3 text-sm text-gray-500">{{ $footer }}</div>
    @endisset
</div>
