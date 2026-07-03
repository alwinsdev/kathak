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

            {{-- Hero: the mudra itself --}}
            <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm ring-1 ring-gray-900/[0.03]">
                <div class="flex flex-col items-center gap-6 p-6 sm:flex-row sm:items-start sm:p-8">
                    <div class="h-40 w-40 shrink-0 overflow-hidden rounded-2xl bg-teal-50 ring-1 ring-gray-900/5">
                        @if ($prescription->mudra->reference_image_path)
                            <img src="{{ asset($prescription->mudra->reference_image_path) }}"
                                 alt="{{ $prescription->mudra->name }}" class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full w-full items-center justify-center text-6xl">🧘</div>
                        @endif
                    </div>
                    <div class="min-w-0 text-center sm:text-left">
                        <div class="text-xs font-semibold uppercase tracking-wide text-teal-600">{{ __('Your prescribed mudra') }}</div>
                        <h3 class="mt-1 text-2xl font-extrabold tracking-tight text-gray-900">{{ $prescription->mudra->name }}</h3>
                        <p class="mt-2 leading-relaxed text-gray-600">{{ $prescription->mudra->description }}</p>
                        @if ($prescription->mudra->benefits)
                            <div class="mt-4 inline-flex items-start gap-2 rounded-xl bg-emerald-50 px-4 py-2.5 text-sm text-emerald-800">
                                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                <span>{{ $prescription->mudra->benefits }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <x-card :title="__('Schedule')">
                <div class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                    <div class="rounded-xl bg-gray-50 p-3">
                        <div class="text-xs uppercase tracking-wide text-gray-400">{{ __('Time') }}</div>
                        <div class="mt-1 font-bold text-gray-900">{{ \Illuminate\Support\Str::substr($prescription->scheduled_time, 0, 5) }}</div>
                    </div>
                    <div class="rounded-xl bg-gray-50 p-3">
                        <div class="text-xs uppercase tracking-wide text-gray-400">{{ __('Duration') }}</div>
                        <div class="mt-1 font-bold text-gray-900">{{ $prescription->duration_min }} {{ __('min') }}</div>
                    </div>
                    <div class="rounded-xl bg-gray-50 p-3">
                        <div class="text-xs uppercase tracking-wide text-gray-400">{{ __('Starts') }}</div>
                        <div class="mt-1 font-bold text-gray-900">{{ $prescription->start_date->format('d M Y') }}</div>
                    </div>
                    <div class="rounded-xl bg-gray-50 p-3">
                        <div class="text-xs uppercase tracking-wide text-gray-400">{{ __('Ends') }}</div>
                        <div class="mt-1 font-bold text-gray-900">{{ $prescription->end_date?->format('d M Y') ?? '—' }}</div>
                    </div>
                </div>
                @if ($prescription->notes)
                    <div class="mt-4 border-t border-gray-100 pt-4">
                        <div class="text-xs uppercase tracking-wide text-gray-400">{{ __("Doctor's notes") }}</div>
                        <div class="mt-1 text-gray-700">{{ $prescription->notes }}</div>
                    </div>
                @endif
            </x-card>

            <div class="flex justify-end">
                <a href="{{ route('patient.practice.show', $prescription) }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-teal-600 px-6 py-3 font-semibold text-white shadow-lg shadow-teal-600/25 transition hover:-translate-y-0.5 hover:bg-teal-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                    {{ __('Start Practice') }}
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
