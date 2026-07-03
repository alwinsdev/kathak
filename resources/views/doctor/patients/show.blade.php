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
            <x-card :title="__('Patient Information')">
                <div class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                    <div class="rounded-xl bg-gray-50 p-3">
                        <div class="text-xs uppercase tracking-wide text-gray-400">{{ __('Age') }}</div>
                        <div class="mt-1 font-bold text-gray-900">{{ $patient->patientProfile?->age ?? '—' }}</div>
                    </div>
                    <div class="rounded-xl bg-gray-50 p-3">
                        <div class="text-xs uppercase tracking-wide text-gray-400">{{ __('Gender') }}</div>
                        <div class="mt-1 font-bold text-gray-900">{{ $patient->patientProfile?->gender?->label() ?? '—' }}</div>
                    </div>
                    <div class="rounded-xl bg-gray-50 p-3">
                        <div class="text-xs uppercase tracking-wide text-gray-400">{{ __('Phone') }}</div>
                        <div class="mt-1 font-bold text-gray-900">{{ $patient->patientProfile?->phone ?? '—' }}</div>
                    </div>
                    <div class="rounded-xl bg-gray-50 p-3">
                        <div class="text-xs uppercase tracking-wide text-gray-400">{{ __('Email') }}</div>
                        <div class="mt-1 truncate font-bold text-gray-900">{{ $patient->email }}</div>
                    </div>
                </div>
                @if ($patient->patientProfile?->condition_notes)
                    <div class="mt-4 border-t border-gray-100 pt-4">
                        <div class="text-xs uppercase tracking-wide text-gray-400">{{ __('Condition / Notes') }}</div>
                        <div class="mt-1 text-gray-700">{{ $patient->patientProfile->condition_notes }}</div>
                    </div>
                @endif
            </x-card>

            {{-- Practice adherence (read-only insight for the doctor) --}}
            <x-card>
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h3 class="font-semibold text-gray-800">{{ __('Practice Adherence') }}</h3>
                        <p class="mt-0.5 text-sm text-gray-500">
                            @if ($lastPractice)
                                {{ __('Last practised') }}
                                <span class="font-semibold text-gray-700">
                                    {{ $lastPractice->isToday() ? __('today') : $lastPractice->format('d M Y') }}
                                </span>
                            @else
                                {{ __('No verified practice yet.') }}
                            @endif
                        </p>
                    </div>

                    {{-- Last 7 days strip --}}
                    <div class="flex items-end gap-2">
                        @foreach ($adherence as $day)
                            <div class="flex flex-col items-center gap-1" title="{{ $day['title'] }}">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg text-xs font-bold
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
            </x-card>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                {{-- Add prescription --}}
                <x-card :title="__('Add Prescription')">
                    <form method="POST" action="{{ route('doctor.prescriptions.store', $patient) }}" class="space-y-4">
                        @csrf

                        <div>
                            <x-input-label for="mudra_id" :value="__('Mudra')" />
                            <select id="mudra_id" name="mudra_id" required
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
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
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                placeholder="{{ __('e.g. start slowly, 5 reps') }}">{{ old('notes') }}</textarea>
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>

                        <x-primary-button>{{ __('Assign Mudra') }}</x-primary-button>
                    </form>
                </x-card>

                {{-- Active prescriptions --}}
                <x-card :title="__('Active Prescriptions')">
                    @if ($prescriptions->isEmpty())
                        <p class="py-8 text-center text-sm text-gray-500">{{ __('No active prescriptions yet.') }}</p>
                    @else
                        <ul class="space-y-3">
                            @foreach ($prescriptions as $prescription)
                                <li x-data="{ editing: false }" class="rounded-2xl border border-gray-100 p-4 transition hover:border-teal-100">

                                    {{-- Display mode --}}
                                    <div x-show="!editing" class="flex items-start justify-between gap-4">
                                        <div class="flex min-w-0 items-start gap-3">
                                            <div class="h-12 w-12 shrink-0 overflow-hidden rounded-xl bg-teal-50 ring-1 ring-gray-900/5">
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
                                                        <span class="inline-flex items-center gap-1 rounded-full bg-teal-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-teal-700">
                                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                                            {{ __('Done today') }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="mt-1 flex items-center gap-1 text-xs text-gray-500">
                                                    <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                    {{ \Illuminate\Support\Str::substr($prescription->scheduled_time, 0, 5) }}
                                                    · {{ $prescription->duration_min }} {{ __('min') }}
                                                    · {{ __('from') }} {{ $prescription->start_date->format('d M Y') }}
                                                </div>
                                                @if ($prescription->notes)
                                                    <div class="mt-1 truncate text-xs text-gray-500">{{ $prescription->notes }}</div>
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
                                                    class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50">
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
                </x-card>
            </div>
        </div>
    </div>
</x-app-layout>
