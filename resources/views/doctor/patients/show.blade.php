@php
    $last = $lastPractice;
    [$statusKey, $statusLabel] = match (true) {
        $last?->isToday() => ['today', __('Practised today')],
        $last && $last->greaterThanOrEqualTo(now()->subDays(3)->startOfDay()) => ['recent', __('Missed recently')],
        $last !== null => ['attention', __('Needs attention')],
        default => ['none', __('No activity')],
    };
    $statusStyles = [
        'today' => ['dot' => 'bg-emerald-500', 'chip' => 'bg-emerald-50 text-emerald-700 ring-emerald-100'],
        'recent' => ['dot' => 'bg-amber-400', 'chip' => 'bg-amber-50 text-amber-700 ring-amber-100'],
        'attention' => ['dot' => 'bg-rose-500', 'chip' => 'bg-rose-50 text-rose-700 ring-rose-100'],
        'none' => ['dot' => 'bg-gray-300', 'chip' => 'bg-gray-100 text-gray-500 ring-gray-200'],
    ][$statusKey];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('Patient Record') }}</h2>
            <a href="{{ route('doctor.dashboard') }}" class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 transition hover:text-teal-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                {{ __('Back to patients') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <x-alert type="success">{{ session('status') }}</x-alert>
            @endif

            @if ($errors->any())
                <x-alert type="error">{{ __('Please correct the errors below.') }}</x-alert>
            @endif

            {{-- Patient header band --}}
            <div class="rise-in overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm ring-1 ring-gray-900/[0.03]">
                <div class="flex flex-col gap-5 p-6 sm:flex-row sm:items-start sm:justify-between sm:p-7">
                    <div class="flex min-w-0 items-start gap-4">
                        <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-500 to-emerald-500 text-2xl font-extrabold text-white shadow-md shadow-teal-600/25">
                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($patient->name, 0, 1)) }}
                        </span>
                        <div class="min-w-0">
                            <h3 class="truncate text-2xl font-extrabold tracking-tight text-gray-900">{{ $patient->name }}</h3>
                            <div class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-500">
                                <span class="inline-flex items-center gap-1.5">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    {{ $patient->patientProfile?->age ?? '—' }} · {{ $patient->patientProfile?->gender?->label() ?? '—' }}
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                    {{ $patient->patientProfile?->phone ?? '—' }}
                                </span>
                                <span class="inline-flex min-w-0 items-center gap-1.5">
                                    <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                    <span class="truncate">{{ $patient->email }}</span>
                                </span>
                            </div>

                            @if ($patient->patientProfile?->condition_notes)
                                {{-- Medical condition callout --}}
                                <div class="mt-3 inline-flex max-w-full items-start gap-2 rounded-xl bg-amber-50 px-3.5 py-2 text-sm text-amber-800 ring-1 ring-amber-100">
                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                                    <span class="min-w-0">{{ $patient->patientProfile->condition_notes }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex shrink-0 flex-col items-start gap-1.5 sm:items-end">
                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $statusStyles['chip'] }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $statusStyles['dot'] }}"></span>
                            {{ $statusLabel }}
                        </span>
                        <span class="text-xs text-gray-400 tabular-nums">
                            {{ $last ? __('Last practised').' '.($last->isToday() ? __('today') : $last->format('d M Y')) : __('No sessions yet') }}
                        </span>
                    </div>
                </div>

                {{-- Summary strip --}}
                <div class="grid grid-cols-3 divide-x divide-gray-100 border-t border-gray-100 bg-gray-50/50">
                    <div class="px-6 py-3.5 text-center sm:text-left">
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ __('Total Sessions') }}</div>
                        <div class="mt-0.5 text-lg font-extrabold tabular-nums text-gray-900">{{ $stats->total }}</div>
                    </div>
                    <div class="px-6 py-3.5 text-center sm:text-left">
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ __('This Week') }}</div>
                        <div class="mt-0.5 text-lg font-extrabold tabular-nums text-gray-900">{{ $stats->thisWeek }}</div>
                    </div>
                    <div class="px-6 py-3.5 text-center sm:text-left">
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ __('Day Streak') }}</div>
                        <div class="mt-0.5 inline-flex items-center gap-1 text-lg font-extrabold tabular-nums text-gray-900">
                            {{ $stats->streak }}
                            @if ($stats->streak >= 3)<svg class="h-4 w-4 text-amber-500" fill="currentColor" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>@endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                {{-- LEFT: treatment plan --}}
                <div class="space-y-6 lg:col-span-2" x-data="{ addOpen: {{ $errors->any() ? 'true' : 'false' }} }">

                    {{-- Active prescriptions --}}
                    <div class="rise-in-1 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm ring-1 ring-gray-900/[0.03]">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2.5">
                                <h3 class="font-semibold text-gray-800">{{ __('Active Prescriptions') }}</h3>
                                <span class="rounded-full bg-teal-50 px-2 py-0.5 text-xs font-semibold text-teal-700 tabular-nums">{{ $prescriptions->count() }}</span>
                            </div>
                            <button type="button" @click="addOpen = !addOpen" :aria-expanded="addOpen"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-teal-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm shadow-teal-600/20 transition hover:bg-teal-700">
                                <svg class="h-4 w-4 transition-transform duration-200" :class="addOpen && 'rotate-45'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                <span x-text="addOpen ? '{{ __('Close') }}' : '{{ __('Add Prescription') }}'">{{ __('Add Prescription') }}</span>
                            </button>
                        </div>

                        {{-- Add prescription panel (collapsible) --}}
                        <div x-show="addOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-cloak
                             class="mb-5 rounded-2xl border border-teal-100 bg-teal-50/40 p-5">
                            <form method="POST" action="{{ route('doctor.prescriptions.store', $patient) }}" class="space-y-4">
                                @csrf

                                <fieldset>
                                    <legend class="text-sm font-medium text-gray-700">{{ __('Mudra') }}</legend>
                                    <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
                                        @foreach ($mudras as $mudra)
                                            <label class="cursor-pointer">
                                                <input type="radio" name="mudra_id" value="{{ $mudra->id }}" class="peer sr-only"
                                                    @checked(old('mudra_id') == $mudra->id) required>
                                                <span class="flex items-center gap-2.5 rounded-xl border border-gray-200 bg-white p-2.5 transition
                                                    peer-checked:border-teal-500 peer-checked:bg-teal-50 peer-checked:ring-1 peer-checked:ring-teal-500
                                                    peer-focus-visible:ring-2 peer-focus-visible:ring-teal-500 peer-focus-visible:ring-offset-2
                                                    hover:border-teal-200">
                                                    @if ($mudra->reference_image_path)
                                                        <img src="{{ asset($mudra->reference_image_path) }}" alt="" class="h-11 w-11 shrink-0 rounded-lg object-cover ring-1 ring-gray-900/5">
                                                    @else
                                                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-teal-400">
                                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11" /></svg>
                                                        </span>
                                                    @endif
                                                    <span class="min-w-0 truncate text-xs font-semibold text-gray-700">{{ $mudra->name }}</span>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <x-input-error :messages="$errors->get('mudra_id')" class="mt-2" />
                                </fieldset>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="scheduled_time" :value="__('Scheduled time')" />
                                        <x-text-input id="scheduled_time" name="scheduled_time" type="time" class="mt-1 block w-full"
                                            :value="old('scheduled_time')" required aria-invalid="{{ $errors->has('scheduled_time') ? 'true' : 'false' }}" />
                                        <x-input-error :messages="$errors->get('scheduled_time')" class="mt-2" />
                                    </div>
                                    <div>
                                        <x-input-label for="duration_min" :value="__('Duration (min)')" />
                                        <x-text-input id="duration_min" name="duration_min" type="number" min="1" max="120" class="mt-1 block w-full"
                                            :value="old('duration_min', 10)" required aria-invalid="{{ $errors->has('duration_min') ? 'true' : 'false' }}" />
                                        <x-input-error :messages="$errors->get('duration_min')" class="mt-2" />
                                    </div>
                                </div>

                                <div>
                                    <x-input-label for="start_date" :value="__('Start date')" />
                                    <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full"
                                        :value="old('start_date', now()->toDateString())" required aria-invalid="{{ $errors->has('start_date') ? 'true' : 'false' }}" />
                                    <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="notes" :value="__('Notes')" />
                                    <textarea id="notes" name="notes" rows="2"
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                        placeholder="{{ __('e.g. start slowly, 5 reps') }}">{{ old('notes') }}</textarea>
                                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                                </div>

                                <div class="flex items-center gap-2">
                                    <x-primary-button>{{ __('Assign Mudra') }}</x-primary-button>
                                    <button type="button" @click="addOpen = false"
                                        class="rounded-lg px-3 py-2 text-xs font-medium text-gray-500 transition hover:bg-gray-100 hover:text-gray-700">
                                        {{ __('Cancel') }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        @if ($prescriptions->isEmpty())
                            {{-- Empty state --}}
                            <div class="flex flex-col items-center rounded-2xl border border-dashed border-gray-200 px-6 py-12 text-center">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-50 text-teal-500">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                </div>
                                <h4 class="mt-3 font-bold text-gray-900">{{ __('No active prescriptions yet.') }}</h4>
                                <p class="mt-1 max-w-xs text-sm text-gray-500">{{ __('Use “Add Prescription” above to assign the first mudra for this patient.') }}</p>
                            </div>
                        @else
                            <ul class="space-y-3">
                                @foreach ($prescriptions as $prescription)
                                    <li x-data="{ editing: false }"
                                        class="rounded-2xl border border-gray-100 p-4 transition hover:border-teal-100 hover:shadow-sm">

                                        {{-- Display mode --}}
                                        <div x-show="!editing" class="flex items-start justify-between gap-4">
                                            <div class="flex min-w-0 items-start gap-3">
                                                <div class="h-14 w-14 shrink-0 overflow-hidden rounded-xl bg-teal-50 ring-1 ring-gray-900/5">
                                                    @if ($prescription->mudra->reference_image_path)
                                                        <img src="{{ asset($prescription->mudra->reference_image_path) }}"
                                                             alt="{{ $prescription->mudra->name }}" class="h-full w-full object-cover">
                                                    @else
                                                        <div class="flex h-full w-full items-center justify-center text-xl">🧘</div>
                                                    @endif
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="font-semibold text-gray-900">{{ $prescription->mudra->name }}</span>
                                                        @if ($doneTodayIds->contains($prescription->id))
                                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700 ring-1 ring-emerald-100">
                                                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                                                {{ __('Done today') }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-gray-500">
                                                        <span class="inline-flex items-center gap-1 tabular-nums">
                                                            <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                            {{ \Illuminate\Support\Str::substr($prescription->scheduled_time, 0, 5) }} · {{ $prescription->duration_min }} {{ __('min') }}
                                                        </span>
                                                        <span class="inline-flex items-center gap-1 tabular-nums">
                                                            <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                            {{ __('since') }} {{ $prescription->start_date->format('d M Y') }}
                                                        </span>
                                                    </div>
                                                    @if ($prescription->notes)
                                                        <div class="mt-1.5 truncate text-xs text-gray-500">{{ $prescription->notes }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="flex shrink-0 items-center gap-2">
                                                <button type="button" @click="editing = true"
                                                    class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:border-teal-300 hover:text-teal-700">
                                                    {{ __('Edit') }}
                                                </button>
                                                <form method="POST" action="{{ route('doctor.prescriptions.destroy', $prescription) }}"
                                                      onsubmit="return confirm('{{ __('Cancel this prescription?') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-medium text-rose-600 transition hover:bg-rose-50">
                                                        {{ __('Cancel') }}
                                                    </button>
                                                </form>
                                            </div>
                                        </div>

                                        {{-- Edit mode (time, duration, notes only) --}}
                                        <form x-show="editing" x-cloak method="POST"
                                              x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                              action="{{ route('doctor.prescriptions.update', $prescription) }}" class="space-y-3">
                                            @csrf
                                            @method('PUT')
                                            <div class="flex items-center gap-2 text-sm font-semibold text-gray-800">
                                                {{ __('Editing') }} — {{ $prescription->mudra->name }}
                                            </div>
                                            <div class="grid grid-cols-2 gap-3">
                                                <div>
                                                    <x-input-label :for="'time_'.$prescription->id" :value="__('Time')" />
                                                    <x-text-input :id="'time_'.$prescription->id" name="scheduled_time" type="time"
                                                        class="mt-1 block w-full"
                                                        :value="\Illuminate\Support\Str::substr($prescription->scheduled_time, 0, 5)" required />
                                                </div>
                                                <div>
                                                    <x-input-label :for="'dur_'.$prescription->id" :value="__('Duration (min)')" />
                                                    <x-text-input :id="'dur_'.$prescription->id" name="duration_min" type="number"
                                                        min="1" max="120" class="mt-1 block w-full"
                                                        :value="$prescription->duration_min" required />
                                                </div>
                                            </div>
                                            <div>
                                                <x-input-label :for="'notes_'.$prescription->id" :value="__('Notes')" />
                                                <textarea :id="'notes_'.$prescription->id" name="notes" rows="2"
                                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">{{ $prescription->notes }}</textarea>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <x-primary-button>{{ __('Save') }}</x-primary-button>
                                                <button type="button" @click="editing = false"
                                                    class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-600 transition hover:bg-gray-50">
                                                    {{ __('Discard') }}
                                                </button>
                                            </div>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    {{-- Previous prescriptions (collapsed) --}}
                    @if ($previousPrescriptions->isNotEmpty())
                        <div class="rise-in-2 rounded-2xl border border-gray-100 bg-white shadow-sm ring-1 ring-gray-900/[0.03]" x-data="{ open: false }">
                            <button type="button" @click="open = !open" :aria-expanded="open"
                                class="flex w-full items-center justify-between px-6 py-4 text-left">
                                <span class="flex items-center gap-2.5">
                                    <svg class="h-4 w-4 text-gray-400 transition-transform duration-200" :class="open && 'rotate-90'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                                    <span class="font-semibold text-gray-800">{{ __('Previous prescriptions') }}</span>
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-500 tabular-nums">{{ $previousPrescriptions->count() }}</span>
                                </span>
                            </button>
                            <ul x-show="open" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                class="divide-y divide-gray-50 border-t border-gray-100">
                                @foreach ($previousPrescriptions as $old)
                                    <li class="flex items-center justify-between gap-3 px-6 py-3">
                                        <span class="min-w-0 truncate text-sm text-gray-600">{{ $old->mudra->name }}</span>
                                        <span class="flex shrink-0 items-center gap-3">
                                            <span class="text-xs text-gray-400 tabular-nums">{{ $old->start_date->format('d M Y') }}</span>
                                            <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $old->status === \App\Enums\PrescriptionStatus::Completed ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                                                {{ $old->status->value }}
                                            </span>
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                {{-- RIGHT: practice evidence --}}
                <div class="space-y-6">

                    {{-- Practice progress --}}
                    <div class="rise-in-2 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm ring-1 ring-gray-900/[0.03]">
                        <h3 class="font-semibold text-gray-800">{{ __('Practice Adherence') }}</h3>
                        <p class="mt-0.5 text-sm text-gray-500">
                            @if ($lastPractice)
                                {{ __('Last practised') }}
                                <span class="font-semibold text-gray-700">{{ $lastPractice->isToday() ? __('today') : $lastPractice->format('d M Y') }}</span>
                            @else
                                {{ __('No verified practice yet.') }}
                            @endif
                        </p>

                        <div class="mt-4 flex items-end justify-between gap-1"
                             role="img" aria-label="{{ __('Practised :done of the last 7 days', ['done' => $adherence->where('done', true)->count()]) }}">
                            @foreach ($adherence as $day)
                                <div class="flex flex-1 flex-col items-center gap-1" aria-hidden="true" title="{{ $day['title'] }}{{ $day['done'] ? ' — '.__('practised') : '' }}">
                                    <div class="flex h-9 w-full max-w-[2.5rem] items-center justify-center rounded-lg text-xs font-bold
                                        {{ $day['done']
                                            ? 'bg-teal-500 text-white shadow-sm shadow-teal-600/30'
                                            : ($day['isToday'] ? 'bg-white text-gray-400 ring-1 ring-teal-300' : 'bg-gray-100 text-gray-300') }}">
                                        @if ($day['done'])
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                        @else
                                            ·
                                        @endif
                                    </div>
                                    <span class="text-[10px] font-medium {{ $day['isToday'] ? 'text-teal-600' : 'text-gray-400' }}">{{ $day['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Recent activity timeline --}}
                    <div class="rise-in-3 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm ring-1 ring-gray-900/[0.03]">
                        <h3 class="font-semibold text-gray-800">{{ __('Recent Activity') }}</h3>

                        @if ($recentActivity->isEmpty())
                            <div class="flex flex-col items-center py-8 text-center">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-50 text-gray-300">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h10a2 2 0 012 2v12a2 2 0 01-2 2z" /></svg>
                                </div>
                                <p class="mt-2 text-sm text-gray-400">{{ __('No sessions yet — progress will appear after the first practice.') }}</p>
                            </div>
                        @else
                            <ol class="relative mt-4 space-y-4 border-s-2 border-gray-100 ps-5">
                                @foreach ($recentActivity as $session)
                                    @php($confidence = $session->best_confidence !== null ? round($session->best_confidence * 100) : null)
                                    <li class="relative"
                                        aria-label="{{ ($session->prescription?->mudra?->name ?? __('Session')).' — '.($confidence !== null ? $confidence.'%' : '—').' · '.$session->practiced_on->format('d M') }}">
                                        <span class="absolute -start-[27px] top-1.5 h-3 w-3 rounded-full border-2 border-white bg-teal-500 ring-1 ring-teal-200"></span>
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="min-w-0 truncate text-sm font-semibold text-gray-800">{{ $session->prescription?->mudra?->name ?? '—' }}</span>
                                            <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-bold tabular-nums
                                                {{ $confidence === null ? 'bg-gray-100 text-gray-500' : ($confidence >= 90 ? 'bg-emerald-50 text-emerald-700' : ($confidence >= 75 ? 'bg-teal-50 text-teal-700' : 'bg-amber-50 text-amber-700')) }}">
                                                {{ $confidence !== null ? $confidence.'%' : '—' }}
                                            </span>
                                        </div>
                                        <div class="mt-0.5 text-xs text-gray-400 tabular-nums">
                                            {{ $session->practiced_on->isToday() ? __('Today') : $session->practiced_on->format('d M Y') }}{{ $session->completed_at ? ' · '.$session->completed_at->format('g:i A') : '' }}
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
