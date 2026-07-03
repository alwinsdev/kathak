@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? __('Good morning') : ($hour < 17 ? __('Good afternoon') : __('Good evening'));
    $firstName = \Illuminate\Support\Str::before(auth()->user()->name, ' ');
    $total = $today->summary->total;
    $completed = $today->summary->completed;
    $pending = $today->summary->pending;
    $next = $today->mudras->first(fn ($m) => ! $m->completedToday);
    $ring = 2 * M_PI * 26;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-2">
            <div>
                <h2 class="text-2xl font-extrabold tracking-tight text-gray-900">{{ $greeting }}, {{ $firstName }} 👋</h2>
                <p class="mt-0.5 text-sm text-gray-500">
                    @if ($total === 0)
                        {{ __('No therapy scheduled for today.') }}
                    @elseif ($pending === 0)
                        {{ __('All done for today — brilliant work!') }}
                    @else
                        {{ trans_choice('You have :count mudra left today|You have :count mudras left today', $pending, ['count' => $pending]) }}
                    @endif
                </p>
            </div>
            <span class="text-sm font-medium text-gray-400">{{ now()->format('l, d M Y') }}</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if ($total === 0)
                {{-- Empty state --}}
                <div class="rise-in flex flex-col items-center rounded-2xl border border-gray-100 bg-white px-6 py-16 text-center shadow-sm ring-1 ring-gray-900/[0.03]">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-50 text-teal-500">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    </div>
                    <h3 class="mt-4 text-lg font-bold text-gray-900">{{ __('No therapy scheduled for today.') }}</h3>
                    <p class="mt-1 max-w-sm text-sm text-gray-500">{{ __('Your doctor will prescribe a routine soon. Check back later.') }}</p>
                </div>
            @else
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                    {{-- HERO: next up / all done --}}
                    <div class="rise-in lg:col-span-2">
                        @if ($next)
                            @php($rx = $next->prescription)
                            <div class="flex h-full flex-col justify-between overflow-hidden rounded-2xl border border-teal-100 bg-gradient-to-br from-teal-50/70 via-white to-white p-6 shadow-sm ring-1 ring-gray-900/[0.03] sm:p-8">
                                <div class="flex flex-col gap-6 sm:flex-row sm:items-start">
                                    <div class="h-28 w-28 shrink-0 overflow-hidden rounded-2xl bg-teal-50 ring-1 ring-gray-900/5">
                                        @if ($rx->mudra->reference_image_path)
                                            <img src="{{ asset($rx->mudra->reference_image_path) }}" alt="{{ $rx->mudra->name }}" class="h-full w-full object-cover">
                                        @else
                                            <div class="flex h-full w-full items-center justify-center text-4xl">🧘</div>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-teal-600 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-white">
                                            <span class="relative flex h-1.5 w-1.5">
                                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-white opacity-70"></span>
                                                <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-white"></span>
                                            </span>
                                            {{ __('Next up') }}
                                        </span>
                                        <h3 class="mt-2 text-2xl font-extrabold tracking-tight text-gray-900">{{ $rx->mudra->name }}</h3>
                                        <p class="mt-1 text-sm leading-relaxed text-gray-600">{{ $rx->mudra->description }}</p>
                                        <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                                            <span class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-1 font-semibold text-gray-600 ring-1 ring-gray-200 tabular-nums">
                                                <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                {{ \Illuminate\Support\Str::substr($rx->scheduled_time, 0, 5) }} · {{ $rx->duration_min }} {{ __('min') }}
                                            </span>
                                            @if ($rx->notes)
                                                <span class="inline-flex max-w-full items-center gap-1 truncate rounded-full bg-white px-2.5 py-1 font-medium text-teal-700 ring-1 ring-teal-100">
                                                    {{ $rx->notes }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-6 flex flex-wrap items-center gap-3">
                                    <a href="{{ route('patient.practice.show', $rx) }}"
                                       class="inline-flex items-center gap-2 rounded-xl bg-teal-600 px-6 py-3 font-semibold text-white shadow-lg shadow-teal-600/25 transition hover:-translate-y-0.5 hover:bg-teal-700">
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z" /></svg>
                                        {{ __('Start Practice') }}
                                    </a>
                                    <a href="{{ route('patient.prescriptions.show', $rx) }}"
                                       class="rounded-xl px-4 py-3 text-sm font-semibold text-gray-500 transition hover:text-teal-700">
                                        {{ __('View details') }}
                                    </a>
                                </div>
                            </div>
                        @else
                            {{-- All done --}}
                            <div class="flex h-full flex-col items-center justify-center rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-white p-8 text-center shadow-sm ring-1 ring-gray-900/[0.03]">
                                <div class="practice-pop flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                                    <svg class="h-9 w-9" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                </div>
                                <h3 class="mt-4 text-2xl font-extrabold tracking-tight text-gray-900">{{ __('All done for today!') }} 🎉</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    {{ trans_choice(':count session completed|:count sessions completed', $completed, ['count' => $completed]) }}@if ($streak > 0) · {{ trans_choice(':count-day streak|:count-day streak', $streak, ['count' => $streak]) }}@endif
                                </p>
                                <a href="{{ route('patient.history') }}"
                                   class="mt-5 inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-600/25 transition hover:-translate-y-0.5 hover:bg-emerald-700">
                                    {{ __('View your progress') }}
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                                </a>
                            </div>
                        @endif
                    </div>

                    {{-- Progress panel --}}
                    <div class="rise-in-1 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm ring-1 ring-gray-900/[0.03]">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __("Today's progress") }}</div>
                        <div class="mt-4 flex items-center justify-center">
                            <div class="relative h-28 w-28">
                                <svg class="h-28 w-28 -rotate-90" viewBox="0 0 64 64">
                                    <circle cx="32" cy="32" r="26" fill="none" stroke="currentColor" stroke-width="7" class="text-gray-100" />
                                    <circle cx="32" cy="32" r="26" fill="none" stroke="currentColor" stroke-width="7" stroke-linecap="round"
                                        class="text-teal-500 transition-all duration-700"
                                        stroke-dasharray="{{ $ring }}"
                                        stroke-dashoffset="{{ $ring * (1 - ($total > 0 ? $completed / $total : 0)) }}" />
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="text-2xl font-extrabold tabular-nums text-gray-900">{{ $completed }}<span class="text-base font-bold text-gray-400">/{{ $total }}</span></span>
                                    <span class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">{{ __('done') }}</span>
                                </div>
                            </div>
                        </div>
                        <dl class="mt-5 space-y-2.5 text-sm">
                            <div class="flex items-center justify-between">
                                <dt class="flex items-center gap-2 text-gray-500"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>{{ __('Completed') }}</dt>
                                <dd class="font-bold tabular-nums text-gray-900">{{ $completed }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="flex items-center gap-2 text-gray-500"><span class="h-2 w-2 rounded-full bg-amber-400"></span>{{ __('Pending') }}</dt>
                                <dd class="font-bold tabular-nums text-gray-900">{{ $pending }}</dd>
                            </div>
                            <div class="flex items-center justify-between border-t border-gray-100 pt-2.5">
                                <dt class="flex items-center gap-2 text-gray-500">
                                    <svg class="h-4 w-4 text-amber-500" fill="currentColor" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                    {{ __('Day streak') }}
                                </dt>
                                <dd class="font-bold tabular-nums text-gray-900">{{ $streak }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                {{-- Today's schedule timeline --}}
                <div class="rise-in-2 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm ring-1 ring-gray-900/[0.03]">
                    <div class="mb-5 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-800">{{ __("Today's schedule") }}</h3>
                        <span class="text-xs font-medium text-gray-400 tabular-nums">{{ $completed }}/{{ $total }} {{ __('completed') }}</span>
                    </div>

                    <ol class="relative ms-2 space-y-4 border-s-2 border-gray-100 ps-6">
                        @foreach ($today->mudras as $item)
                            @php($rx = $item->prescription)
                            @php($isNext = $next && $rx->id === $next->prescription->id)
                            <li class="relative">
                                {{-- rail dot --}}
                                <span class="absolute -start-[31px] top-4 flex h-4 w-4 items-center justify-center">
                                    @if ($item->completedToday)
                                        <span class="flex h-4 w-4 items-center justify-center rounded-full bg-emerald-500">
                                            <svg class="h-2.5 w-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                        </span>
                                    @elseif ($isNext)
                                        <span class="relative flex h-4 w-4">
                                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-teal-400 opacity-60"></span>
                                            <span class="relative inline-flex h-4 w-4 rounded-full border-[3px] border-teal-500 bg-white"></span>
                                        </span>
                                    @else
                                        <span class="h-4 w-4 rounded-full border-2 border-gray-200 bg-white"></span>
                                    @endif
                                </span>

                                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border p-3.5 transition
                                    {{ $item->completedToday ? 'border-gray-100 bg-gray-50/60' : ($isNext ? 'border-teal-100 bg-teal-50/40' : 'border-gray-100 bg-white hover:border-teal-100 hover:shadow-sm') }}">
                                    <div class="flex min-w-0 items-center gap-3.5">
                                        <span class="w-12 shrink-0 text-sm font-bold tabular-nums {{ $item->completedToday ? 'text-gray-400' : 'text-gray-700' }}">
                                            {{ \Illuminate\Support\Str::substr($rx->scheduled_time, 0, 5) }}
                                        </span>
                                        <div class="h-12 w-12 shrink-0 overflow-hidden rounded-lg bg-teal-50 ring-1 ring-gray-900/5">
                                            @if ($rx->mudra->reference_image_path)
                                                <img src="{{ asset($rx->mudra->reference_image_path) }}" alt="{{ $rx->mudra->name }}"
                                                     class="h-full w-full object-cover {{ $item->completedToday ? 'opacity-60 saturate-50' : '' }}">
                                            @else
                                                <div class="flex h-full w-full items-center justify-center text-xl">🧘</div>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-semibold {{ $item->completedToday ? 'text-gray-500' : 'text-gray-900' }}">{{ $rx->mudra->name }}</div>
                                            <div class="truncate text-xs text-gray-500">{{ $rx->duration_min }} {{ __('min') }}<span class="hidden sm:inline"> · {{ $rx->mudra->description }}</span></div>
                                        </div>
                                    </div>

                                    <div class="flex shrink-0 items-center gap-2">
                                        @if ($item->completedToday)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                                {{ __('Done') }}
                                            </span>
                                        @else
                                            <a href="{{ route('patient.prescriptions.show', $rx) }}"
                                               class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:border-teal-300 hover:text-teal-700">
                                                {{ __('Details') }}
                                            </a>
                                            <a href="{{ route('patient.practice.show', $rx) }}"
                                               class="inline-flex items-center gap-1.5 rounded-lg {{ $isNext ? 'bg-teal-600 text-white shadow-sm shadow-teal-600/20 hover:bg-teal-700' : 'border border-teal-200 text-teal-700 hover:bg-teal-50' }} px-3.5 py-1.5 text-xs font-semibold transition">
                                                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z" /></svg>
                                                {{ __('Practice') }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
