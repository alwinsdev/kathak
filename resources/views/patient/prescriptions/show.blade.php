<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ $prescription->mudra->name }}
            </h2>
            <a href="{{ route('patient.dashboard') }}" class="text-sm text-gray-500 hover:text-teal-700">
                &larr; {{ __('Back to today') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">

            <x-card :title="__('About this mudra')">
                <p class="text-gray-700">{{ $prescription->mudra->description }}</p>
                @if ($prescription->mudra->benefits)
                    <div class="mt-3 flex items-start gap-2 rounded-lg bg-green-50 p-3 text-sm text-green-800">
                        <span>✓</span><span>{{ $prescription->mudra->benefits }}</span>
                    </div>
                @endif
            </x-card>

            <x-card :title="__('Schedule')">
                <div class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-500">{{ __('Time') }}</div>
                        <div class="mt-1 font-medium">{{ \Illuminate\Support\Str::substr($prescription->scheduled_time, 0, 5) }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-500">{{ __('Duration') }}</div>
                        <div class="mt-1 font-medium">{{ $prescription->duration_min }} {{ __('min') }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-500">{{ __('Starts') }}</div>
                        <div class="mt-1 font-medium">{{ $prescription->start_date->format('d M Y') }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-500">{{ __('Ends') }}</div>
                        <div class="mt-1 font-medium">{{ $prescription->end_date?->format('d M Y') ?? '—' }}</div>
                    </div>
                </div>
                @if ($prescription->notes)
                    <div class="mt-4 border-t border-gray-100 pt-4">
                        <div class="text-xs uppercase tracking-wide text-gray-500">{{ __("Doctor's notes") }}</div>
                        <div class="mt-1 text-gray-700">{{ $prescription->notes }}</div>
                    </div>
                @endif
            </x-card>

            <div class="flex justify-end">
                <a href="{{ route('patient.practice.show', $prescription) }}"
                   class="rounded-md bg-teal-600 px-5 py-2.5 font-medium text-white hover:bg-teal-700">
                    📷 {{ __('Start Practice') }}
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
