<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ $patient->name }}
            </h2>
            <a href="{{ route('doctor.dashboard') }}" class="text-sm text-gray-500 hover:text-teal-700">
                &larr; {{ __('Back to patients') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <x-alert type="success">{{ session('status') }}</x-alert>
            @endif

            @if ($errors->any())
                <x-alert type="error">
                    {{ __('Please correct the errors below.') }}
                </x-alert>
            @endif

            {{-- Patient info --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 font-semibold text-gray-800">{{ __('Patient Information') }}</h3>
                <div class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-500">{{ __('Age') }}</div>
                        <div class="mt-1 font-medium">{{ $patient->patientProfile?->age ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-500">{{ __('Gender') }}</div>
                        <div class="mt-1 font-medium">{{ $patient->patientProfile?->gender?->label() ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-500">{{ __('Phone') }}</div>
                        <div class="mt-1 font-medium">{{ $patient->patientProfile?->phone ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-500">{{ __('Email') }}</div>
                        <div class="mt-1 font-medium">{{ $patient->email }}</div>
                    </div>
                </div>
                @if ($patient->patientProfile?->condition_notes)
                    <div class="mt-4 border-t border-gray-100 pt-4">
                        <div class="text-xs uppercase tracking-wide text-gray-500">{{ __('Condition / Notes') }}</div>
                        <div class="mt-1 text-gray-700">{{ $patient->patientProfile->condition_notes }}</div>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                {{-- Add prescription --}}
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-4 font-semibold text-gray-800">{{ __('Add Prescription') }}</h3>
                    <form method="POST" action="{{ route('doctor.prescriptions.store', $patient) }}" class="space-y-4">
                        @csrf

                        <div>
                            <x-input-label for="mudra_id" :value="__('Mudra')" />
                            <select id="mudra_id" name="mudra_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                                <option value="">{{ __('-- Choose a mudra --') }}</option>
                                @foreach ($mudras as $mudra)
                                    <option value="{{ $mudra->id }}" @selected(old('mudra_id') == $mudra->id)>{{ $mudra->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('mudra_id')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="scheduled_time" :value="__('Scheduled time')" />
                                <x-text-input id="scheduled_time" name="scheduled_time" type="time" class="mt-1 block w-full"
                                    :value="old('scheduled_time')" required />
                                <x-input-error :messages="$errors->get('scheduled_time')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="duration_min" :value="__('Duration (min)')" />
                                <x-text-input id="duration_min" name="duration_min" type="number" min="1" max="120" class="mt-1 block w-full"
                                    :value="old('duration_min', 10)" required />
                                <x-input-error :messages="$errors->get('duration_min')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="start_date" :value="__('Start date')" />
                            <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full"
                                :value="old('start_date', now()->toDateString())" required />
                            <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="notes" :value="__('Notes')" />
                            <textarea id="notes" name="notes" rows="2"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                placeholder="{{ __('e.g. start slowly, 5 reps') }}">{{ old('notes') }}</textarea>
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>

                        <x-primary-button>{{ __('Assign Mudra') }}</x-primary-button>
                    </form>
                </div>

                {{-- Active prescriptions --}}
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-4 font-semibold text-gray-800">{{ __('Active Prescriptions') }}</h3>

                    @if ($prescriptions->isEmpty())
                        <p class="py-8 text-center text-sm text-gray-500">{{ __('No active prescriptions yet.') }}</p>
                    @else
                        <ul class="space-y-3">
                            @foreach ($prescriptions as $prescription)
                                <li x-data="{ editing: false }" class="rounded-lg border border-gray-200 p-4">

                                    {{-- Display mode --}}
                                    <div x-show="!editing" class="flex items-start justify-between gap-4">
                                        <div>
                                            <div class="font-medium text-gray-900">{{ $prescription->mudra->name }}</div>
                                            <div class="mt-1 text-xs text-gray-500">
                                                ⏰ {{ \Illuminate\Support\Str::substr($prescription->scheduled_time, 0, 5) }}
                                                · {{ $prescription->duration_min }} {{ __('min') }}
                                                · {{ __('from') }} {{ $prescription->start_date->format('d M Y') }}
                                            </div>
                                            @if ($prescription->notes)
                                                <div class="mt-1 text-xs text-gray-500">📝 {{ $prescription->notes }}</div>
                                            @endif
                                        </div>
                                        <div class="flex shrink-0 items-center gap-2">
                                            <button type="button" @click="editing = true"
                                                class="rounded-md border border-gray-300 px-3 py-1 text-xs font-medium text-gray-700 hover:border-teal-500">
                                                {{ __('Edit') }}
                                            </button>
                                            <form method="POST" action="{{ route('doctor.prescriptions.destroy', $prescription) }}"
                                                  onsubmit="return confirm('{{ __('Cancel this prescription?') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="rounded-md bg-red-600 px-3 py-1 text-xs font-medium text-white hover:bg-red-700">
                                                    {{ __('Cancel') }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    {{-- Edit mode (time, duration, notes only) --}}
                                    <form x-show="editing" x-cloak method="POST"
                                          action="{{ route('doctor.prescriptions.update', $prescription) }}" class="space-y-3">
                                        @csrf
                                        @method('PUT')
                                        <div class="text-sm font-medium text-gray-800">{{ $prescription->mudra->name }}</div>
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
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">{{ $prescription->notes }}</textarea>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <x-primary-button>{{ __('Save') }}</x-primary-button>
                                            <button type="button" @click="editing = false"
                                                class="rounded-md border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50">
                                                {{ __('Discard') }}
                                            </button>
                                        </div>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
