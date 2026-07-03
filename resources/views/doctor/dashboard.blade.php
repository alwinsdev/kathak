@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? __('Good morning') : ($hour < 17 ? __('Good afternoon') : __('Good evening'));
    $firstName = \Illuminate\Support\Str::before(auth()->user()->name, ' ');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-2">
            <div>
                <h2 class="text-2xl font-extrabold tracking-tight text-gray-900">{{ $greeting }}, {{ $firstName }} 👋</h2>
                <p class="mt-0.5 text-sm text-gray-500">
                    @if ($totalPatients === 0)
                        {{ __('No patients assigned yet.') }}
                    @else
                        {{ __(':done of :total patients practised today', ['done' => $practisedToday, 'total' => $totalPatients]) }}
                    @endif
                </p>
            </div>
            <span class="text-sm font-medium text-gray-400">{{ now()->format('l, d M Y') }}</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <x-alert type="success">{{ session('status') }}</x-alert>
            @endif

            {{-- KPI row --}}
            <div class="rise-in grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-stat-card label="My Patients" :value="$totalPatients" icon="patients">
                    <x-slot name="footer">{{ __('Assigned to your panel') }}</x-slot>
                </x-stat-card>
                <x-stat-card label="Active Prescriptions" :value="$totalActivePrescriptions" icon="prescriptions">
                    <x-slot name="footer">{{ __('Across all patients') }}</x-slot>
                </x-stat-card>
                <x-stat-card label="Practised Today" :value="$practisedToday.' / '.$totalPatients" icon="check">
                    <x-slot name="footer">{{ $practisedToday === $totalPatients && $totalPatients > 0 ? __('Everyone is on track 🎉') : __('Patients with a verified session') }}</x-slot>
                </x-stat-card>
            </div>

            {{-- Patient list --}}
            <div class="rise-in-1 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm ring-1 ring-gray-900/[0.03]">
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                    <h3 class="font-semibold text-gray-800">{{ __('Patient List') }}</h3>
                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-500 tabular-nums">{{ $totalPatients }} {{ __('total') }}</span>
                </div>

                @if ($patients->isEmpty())
                    {{-- Designed empty state --}}
                    <div class="flex flex-col items-center px-6 py-16 text-center">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-50 text-teal-500">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        </div>
                        <h4 class="mt-4 font-bold text-gray-900">{{ __('No patients are assigned to you yet.') }}</h4>
                        <p class="mt-1 max-w-sm text-sm text-gray-500">{{ __('Patients choose their doctor during registration — new patients will appear here automatically.') }}</p>
                    </div>
                @else
                    {{-- Column headers (desktop) --}}
                    <div class="hidden border-b border-gray-100 bg-gray-50/60 px-6 py-2.5 text-xs font-semibold uppercase tracking-wide text-gray-400 md:grid md:grid-cols-[minmax(0,1.7fr),minmax(0,1.3fr),auto,auto,minmax(0,1fr),auto] md:items-center md:gap-4">
                        <span>{{ __('Patient') }}</span>
                        <span>{{ __('Condition') }}</span>
                        <span class="text-center">{{ __('Rx') }}</span>
                        <span>{{ __('Last 7 days') }}</span>
                        <span>{{ __('Status') }}</span>
                        <span class="sr-only">{{ __('Actions') }}</span>
                    </div>

                    <ul class="divide-y divide-gray-50">
                        @foreach ($patients as $index => $patient)
                            @php
                                $last = $patient->last_practice_date;
                                [$statusKey, $statusLabel] = match (true) {
                                    $last?->isToday() => ['today', __('Practised today')],
                                    $last && $last->greaterThanOrEqualTo(now()->subDays(3)->startOfDay()) => ['recent', __('Missed recently')],
                                    $last !== null => ['attention', __('Needs attention')],
                                    default => ['none', __('No activity')],
                                };
                                $statusStyles = [
                                    'today' => ['dot' => 'bg-emerald-500', 'chip' => 'bg-emerald-50 text-emerald-700'],
                                    'recent' => ['dot' => 'bg-amber-400', 'chip' => 'bg-amber-50 text-amber-700'],
                                    'attention' => ['dot' => 'bg-rose-500', 'chip' => 'bg-rose-50 text-rose-700'],
                                    'none' => ['dot' => 'bg-gray-300', 'chip' => 'bg-gray-100 text-gray-500'],
                                ][$statusKey];
                                $doneCount = collect($patient->adherence_days)->where('done', true)->count();
                            @endphp
                            <li class="rise-in" style="animation-delay: {{ min($index, 6) * 40 }}ms">
                                <a href="{{ route('doctor.patients.show', $patient) }}"
                                   class="group grid grid-cols-1 gap-3 px-6 py-4 transition hover:bg-teal-50/40 md:grid-cols-[minmax(0,1.7fr),minmax(0,1.3fr),auto,auto,minmax(0,1fr),auto] md:items-center md:gap-4">

                                    {{-- Patient --}}
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-emerald-500 text-sm font-bold text-white shadow-sm">
                                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($patient->name, 0, 1)) }}
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block truncate font-semibold text-gray-900">{{ $patient->name }}</span>
                                            <span class="block truncate text-xs text-gray-500">{{ $patient->email }}</span>
                                        </span>
                                    </div>

                                    {{-- Condition --}}
                                    <span class="truncate text-sm text-gray-600" title="{{ $patient->patientProfile?->condition_notes }}">
                                        {{ $patient->patientProfile?->condition_notes ?: '—' }}
                                    </span>

                                    {{-- Active Rx --}}
                                    <span class="justify-self-start md:justify-self-center">
                                        <x-badge color="teal">{{ $patient->active_prescriptions_count }}</x-badge>
                                    </span>

                                    {{-- 7-day adherence dots --}}
                                    <span class="flex items-center gap-1"
                                          role="img"
                                          aria-label="{{ __('Practised :done of the last 7 days', ['done' => $doneCount]) }}">
                                        @foreach ($patient->adherence_days as $day)
                                            <span aria-hidden="true" title="{{ $day['title'] }}{{ $day['done'] ? ' — '.__('practised') : '' }}"
                                                  class="h-2.5 w-2.5 rounded-[3px] {{ $day['done'] ? 'bg-teal-500' : ($day['isToday'] ? 'bg-white ring-1 ring-teal-400' : 'bg-gray-200') }}"></span>
                                        @endforeach
                                    </span>

                                    {{-- Status --}}
                                    <span class="flex min-w-0 flex-col gap-0.5">
                                        <span class="inline-flex w-fit items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusStyles['chip'] }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $statusStyles['dot'] }}"></span>
                                            {{ $statusLabel }}
                                        </span>
                                        <span class="text-[11px] text-gray-400 tabular-nums">
                                            {{ $last ? __('Last').': '.$last->format('d M') : __('No sessions yet') }}
                                        </span>
                                    </span>

                                    {{-- CTA --}}
                                    <span class="inline-flex w-fit items-center gap-1 rounded-lg bg-teal-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm shadow-teal-600/20 transition group-hover:bg-teal-700 md:justify-self-end">
                                        {{ __('Manage') }}
                                        <svg class="h-3.5 w-3.5 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
