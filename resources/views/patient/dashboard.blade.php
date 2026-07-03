<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('My Therapy') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-stat-card label="Today's Therapy" :value="$today->summary->total" icon="therapy" />
                <x-stat-card label="Completed" :value="$today->summary->completed" icon="check" />
                <x-stat-card label="Pending" :value="$today->summary->pending" icon="pending" />
            </div>

            <x-card>
                @php
                    $total = $today->summary->total;
                    $completed = $today->summary->completed;
                    $pct = $total > 0 ? $completed / $total : 0;
                    $ringCircumference = 2 * M_PI * 26;
                    $nextId = $today->mudras->first(fn ($m) => ! $m->completedToday)?->prescription->id;
                @endphp

                <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        {{-- Daily progress ring --}}
                        <div class="relative h-16 w-16">
                            <svg class="h-16 w-16 -rotate-90" viewBox="0 0 64 64">
                                <circle cx="32" cy="32" r="26" fill="none" stroke="currentColor" stroke-width="7" class="text-gray-100" />
                                <circle cx="32" cy="32" r="26" fill="none" stroke="currentColor" stroke-width="7" stroke-linecap="round"
                                    class="text-teal-500 transition-all duration-700"
                                    stroke-dasharray="{{ $ringCircumference }}"
                                    stroke-dashoffset="{{ $ringCircumference * (1 - $pct) }}" />
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center text-sm font-extrabold text-gray-800">
                                {{ $completed }}/{{ $total }}
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800">{{ __("Today's Therapy") }}</h3>
                            <p class="text-sm text-gray-500">
                                @if ($total === 0)
                                    {{ __('Nothing scheduled today.') }}
                                @elseif ($completed === $total)
                                    {{ __('All done — brilliant work! 🎉') }}
                                @else
                                    {{ __(':count to go — keep it up!', ['count' => $total - $completed]) }}
                                @endif
                            </p>
                        </div>
                    </div>
                    <span class="text-sm font-medium text-gray-400">{{ now()->format('l, d M Y') }}</span>
                </div>

                @if ($today->mudras->isEmpty())
                    <div class="py-12 text-center text-gray-500">
                        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-50 text-teal-500">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        </div>
                        {{ __('No therapy scheduled for today.') }}
                        <div class="mt-1 text-sm">{{ __('Your doctor will prescribe a routine soon.') }}</div>
                    </div>
                @else
                    <ul class="space-y-3">
                        @foreach ($today->mudras as $item)
                            @php($prescription = $item->prescription)
                            @php($isNext = $prescription->id === $nextId)
                            <li class="group relative flex flex-wrap items-center justify-between gap-4 rounded-2xl border p-4 transition
                                {{ $item->completedToday
                                    ? 'border-gray-100 bg-gray-50/60'
                                    : ($isNext ? 'border-teal-200 bg-teal-50/40 ring-1 ring-teal-100' : 'border-gray-100 bg-white hover:border-teal-100 hover:shadow-sm') }}">

                                <div class="flex min-w-0 items-center gap-4">
                                    {{-- Mudra photo --}}
                                    <div class="relative h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-teal-50 ring-1 ring-gray-900/5">
                                        @if ($prescription->mudra->reference_image_path)
                                            <img src="{{ asset($prescription->mudra->reference_image_path) }}"
                                                 alt="{{ $prescription->mudra->name }}"
                                                 class="h-full w-full object-cover {{ $item->completedToday ? 'opacity-60 saturate-50' : '' }}">
                                        @else
                                            <div class="flex h-full w-full items-center justify-center text-2xl">🧘</div>
                                        @endif
                                        @if ($item->completedToday)
                                            <div class="absolute inset-0 flex items-center justify-center bg-teal-600/40">
                                                <svg class="h-7 w-7 text-white drop-shadow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-semibold {{ $item->completedToday ? 'text-gray-500' : 'text-gray-900' }}">{{ $prescription->mudra->name }}</span>
                                            @if ($isNext)
                                                <span class="rounded-full bg-teal-600 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">{{ __('Next up') }}</span>
                                            @endif
                                        </div>
                                        <div class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-gray-500">
                                            <span class="inline-flex items-center gap-1">
                                                <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                {{ \Illuminate\Support\Str::substr($prescription->scheduled_time, 0, 5) }} · {{ $prescription->duration_min }} {{ __('min') }}
                                            </span>
                                            <span class="hidden truncate sm:inline">{{ $prescription->mudra->description }}</span>
                                        </div>
                                        @if ($prescription->notes)
                                            <div class="mt-1 truncate text-xs text-teal-700">{{ $prescription->notes }}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex shrink-0 items-center gap-2">
                                    @if ($item->completedToday)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-teal-100 px-3 py-1 text-xs font-semibold text-teal-700">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                            {{ __('Done') }}
                                        </span>
                                        <a href="{{ route('patient.prescriptions.show', $prescription) }}"
                                           class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-500 transition hover:border-teal-300 hover:text-teal-700">
                                            {{ __('Details') }}
                                        </a>
                                    @else
                                        <a href="{{ route('patient.prescriptions.show', $prescription) }}"
                                           class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:border-teal-300 hover:text-teal-700">
                                            {{ __('Details') }}
                                        </a>
                                        <a href="{{ route('patient.practice.show', $prescription) }}"
                                           class="inline-flex items-center gap-1.5 rounded-lg bg-teal-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm shadow-teal-600/20 transition hover:bg-teal-700">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                            {{ __('Practice') }}
                                        </a>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
