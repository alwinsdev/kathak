@props([
    'label' => '',
    'value' => '',
    'icon' => null,
    'skeleton' => false,
])

@php
    $paths = [
        'patients' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
        'prescriptions' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
        'calendar' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        'sessions' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h10a2 2 0 012 2v12a2 2 0 01-2 2z',
        'streak' => 'M13 10V3L4 14h7v7l9-11h-7z',
        'clock' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        'check' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'pending' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        'therapy' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
    ];
    $path = $paths[$icon] ?? null;
@endphp

<div {{ $attributes->merge(['class' => 'group relative flex h-full flex-col justify-between overflow-hidden rounded-2xl border border-gray-100 bg-white p-5 shadow-sm ring-1 ring-gray-900/[0.03] transition duration-200 hover:-translate-y-0.5 hover:shadow-md']) }}>
    @if ($skeleton)
        {{-- Loading skeleton --}}
        <div class="animate-pulse" aria-hidden="true">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <div class="h-3 w-20 rounded bg-gray-100"></div>
                    <div class="mt-3 h-7 w-14 rounded bg-gray-200"></div>
                </div>
                <div class="h-11 w-11 shrink-0 rounded-xl bg-gray-100"></div>
            </div>
            <div class="mt-3 h-3 w-28 rounded bg-gray-100"></div>
        </div>
    @else
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $label }}</div>
                <div class="mt-2 truncate text-2xl font-extrabold tabular-nums tracking-tight text-gray-900">{{ $value }}</div>
            </div>
            @if ($path)
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-teal-500 to-emerald-500 text-white shadow-sm shadow-teal-600/25 transition-transform duration-200 group-hover:scale-110">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" /></svg>
                </div>
            @elseif ($icon)
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-lg">{{ $icon }}</div>
            @endif
        </div>

        @isset($footer)
            <div class="mt-3 text-xs font-medium text-gray-400">{{ $footer }}</div>
        @endisset
    @endif
</div>
