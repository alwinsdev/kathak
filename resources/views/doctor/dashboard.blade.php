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
                <x-stat-card label="My Patients" :value="$totalPatients" icon="👥" />
                <x-stat-card label="Active Prescriptions" :value="$totalActivePrescriptions" icon="📋" />
                <x-stat-card label="Today" :value="now()->format('d M Y')" icon="📅" />
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
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
                                        <span class="inline-flex items-center rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-semibold text-teal-700">
                                            {{ $patient->active_prescriptions_count }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('doctor.patients.show', $patient) }}"
                                           class="rounded-md bg-teal-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-teal-700">
                                            {{ __('Manage') }}
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
