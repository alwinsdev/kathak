<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('My Patients') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <x-alert type="success">{{ session('status') }}</x-alert>
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-stat-card label="My Patients" :value="$totalPatients" icon="patients" />
                <x-stat-card label="Active Prescriptions" :value="$totalActivePrescriptions" icon="prescriptions" />
                <x-stat-card label="Today" :value="now()->format('d M Y')" icon="calendar" />
            </div>

            <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm ring-1 ring-gray-900/[0.03]">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h3 class="font-semibold text-gray-800">{{ __('Patient List') }}</h3>
                </div>

                @if ($patients->isEmpty())
                    <div class="px-6 py-12 text-center text-gray-500">
                        {{ __('No patients are assigned to you yet.') }}
                    </div>
                @else
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-6 py-3 font-semibold">{{ __('Patient') }}</th>
                                <th class="px-6 py-3 font-semibold">{{ __('Condition') }}</th>
                                <th class="px-6 py-3 font-semibold">{{ __('Active') }}</th>
                                <th class="px-6 py-3 font-semibold">{{ __('Last practice') }}</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($patients as $patient)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900">{{ $patient->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $patient->email }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600">
                                        {{ $patient->patientProfile?->condition_notes ?: '—' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <x-badge color="teal">{{ $patient->active_prescriptions_count }}</x-badge>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($patient->last_practice_date?->isToday())
                                            <span class="inline-flex items-center gap-1 rounded-full bg-teal-100 px-2.5 py-0.5 text-xs font-semibold text-teal-700">
                                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                                {{ __('Today') }}
                                            </span>
                                        @elseif ($patient->last_practice_date)
                                            <span class="text-gray-600">{{ $patient->last_practice_date->format('d M Y') }}</span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('doctor.patients.show', $patient) }}"
                                           class="inline-flex items-center gap-1 rounded-lg bg-teal-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm shadow-teal-600/20 transition hover:bg-teal-700">
                                            {{ __('Manage') }}
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
