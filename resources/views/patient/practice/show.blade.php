<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Practice') }}: {{ $prescription->mudra->name }}
            </h2>
            <a href="{{ route('patient.dashboard') }}" class="text-sm text-gray-500 hover:text-teal-700">
                &larr; {{ __('Back to today') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div id="practice-root"
             class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8"
             data-start-url="{{ route('patient.practice.start', $prescription) }}"
             data-detect-template="{{ route('patient.practice.detect', ['session' => '__SESSION__']) }}"
             data-target="{{ $prescription->mudra->name }}"
             data-jpeg-quality="{{ $practiceConfig['jpegQuality'] }}"
             data-hold-seconds="{{ $practiceConfig['holdSeconds'] }}"
             data-detection-interval-ms="{{ $practiceConfig['detectionIntervalMs'] }}">

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {{-- Camera --}}
                <div class="lg:col-span-2">
                    <div class="relative overflow-hidden rounded-xl bg-slate-900" style="min-height:320px">
                        <video id="practice-video" autoplay playsinline muted class="block w-full"></video>
                        <canvas id="practice-overlay" class="pointer-events-none absolute inset-0 h-full w-full"></canvas>
                    </div>
                </div>

                {{-- Status / controls --}}
                <x-card>
                    <h3 class="font-semibold text-gray-800">{{ __('Detection Status') }}</h3>
                    <p id="practice-status" class="mt-2 rounded-md bg-gray-50 p-3 text-sm text-gray-600">
                        {{ __('Press Start to begin.') }}
                    </p>

                    {{-- Hold progress (width driven by the server's held_seconds) --}}
                    <div class="mt-4">
                        <div class="mb-1 flex items-center justify-between text-xs text-gray-500">
                            <span>{{ __('Hold') }}</span>
                            <span id="practice-hold-label">0%</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200">
                            <div id="practice-hold-bar" class="h-full w-0 bg-teal-500 transition-all duration-300"></div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button id="practice-start"
                                class="w-full rounded-md bg-teal-600 px-4 py-2 font-medium text-white hover:bg-teal-700">
                            ▶ {{ __('Start') }}
                        </button>
                        <button id="practice-stop"
                                class="hidden w-full rounded-md border border-gray-300 px-4 py-2 font-medium text-gray-700 hover:bg-gray-50">
                            ⏹ {{ __('Stop') }}
                        </button>
                    </div>

                    <div class="mt-5 border-t border-gray-100 pt-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Target mudra') }}</div>
                        <div class="mt-1 font-medium text-gray-900">{{ $prescription->mudra->name }}</div>
                        <p class="mt-1 text-xs text-gray-500">{{ $prescription->mudra->description }}</p>
                    </div>
                </x-card>
            </div>
        </div>
    </div>

    @push('scripts')
        @vite('resources/js/practice/practice.js')
    @endpush
</x-app-layout>
