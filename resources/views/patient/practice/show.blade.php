<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('patient.dashboard') }}" class="text-sm text-gray-500 hover:text-teal-700">&larr; {{ __('Back to today') }}</a>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ __('Practice') }}: {{ $prescription->mudra->name }}
                </h2>
                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">
                    {{ __('Hold') }} {{ $practiceConfig['holdSeconds'] }}s · {{ __('Confidence') }} ≥ {{ (int) round($practiceConfig['confidenceThreshold'] * 100) }}%
                </span>
            </div>
            <a href="#mudra-guide" class="text-sm font-medium text-teal-700 hover:text-teal-800">❓ {{ __('Need help?') }}</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div id="practice-root"
             class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
             data-start-url="{{ route('patient.practice.start', $prescription) }}"
             data-detect-template="{{ route('patient.practice.detect', ['session' => '__SESSION__']) }}"
             data-target="{{ $prescription->mudra->name }}"
             data-jpeg-quality="{{ $practiceConfig['jpegQuality'] }}"
             data-hold-seconds="{{ $practiceConfig['holdSeconds'] }}"
             data-detection-interval-ms="{{ $practiceConfig['detectionIntervalMs'] }}">

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                {{-- LEFT: camera + guide --}}
                <div class="space-y-6 lg:col-span-2">
                    {{-- Camera --}}
                    <div class="relative overflow-hidden rounded-xl bg-slate-900 shadow-sm" style="min-height:320px">
                        <video id="practice-video" autoplay playsinline muted class="block w-full"></video>
                        <canvas id="practice-overlay" class="pointer-events-none absolute inset-0 h-full w-full"></canvas>
                        <div id="practice-camera-pill"
                             class="absolute left-3 top-3 hidden items-center gap-1.5 rounded-full bg-black/55 px-3 py-1 text-xs font-medium text-white">
                            <span class="h-2 w-2 rounded-full bg-green-400"></span>{{ __('Camera Active') }}
                        </div>
                        <div id="practice-resolution"
                             class="absolute bottom-3 right-3 rounded bg-black/55 px-2 py-0.5 text-xs text-white/90"></div>
                    </div>

                    {{-- Mudra teaching guide --}}
                    <x-card id="mudra-guide">
                        <h3 class="mb-4 font-semibold text-gray-800">{{ __('How to do') }} {{ $prescription->mudra->name }} {{ __('mudra') }}</h3>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('The shape') }}</div>
                                <div class="mt-2 flex h-28 items-center justify-center rounded-lg bg-teal-50 text-6xl">
                                    @if ($prescription->mudra->reference_image_path)
                                        <img src="{{ asset($prescription->mudra->reference_image_path) }}" alt="{{ $prescription->mudra->name }}" class="h-full object-contain">
                                    @else
                                        <span>{{ $guide['symbol'] }}</span>
                                    @endif
                                </div>
                                <p class="mt-2 text-sm text-gray-600">{{ $prescription->mudra->description }}</p>
                            </div>
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Steps') }}</div>
                                <ol class="mt-2 space-y-2 text-sm text-gray-700">
                                    @foreach ($guide['steps'] as $i => $step)
                                        <li class="flex gap-2">
                                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-teal-600 text-[11px] font-semibold text-white">{{ $i + 1 }}</span>
                                            <span>{{ $step }}</span>
                                        </li>
                                    @endforeach
                                </ol>
                            </div>
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Common mistakes') }}</div>
                                <ul class="mt-2 space-y-2 text-sm text-gray-700">
                                    @foreach ($guide['mistakes'] as $mistake)
                                        <li class="flex gap-2"><span class="text-red-500">✗</span><span>{{ $mistake }}</span></li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </x-card>
                </div>

                {{-- RIGHT: detection, progress, session --}}
                <div class="space-y-6">
                    {{-- Detection status --}}
                    <x-card>
                        <h3 class="font-semibold text-gray-800">{{ __('Detection Status') }}</h3>

                        <div class="mt-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span id="practice-status-dot" class="h-2.5 w-2.5 rounded-full bg-gray-300"></span>
                                <span id="practice-detected" class="text-sm font-medium text-gray-700">{{ __('Press Start to begin') }}</span>
                            </div>
                            <span id="practice-confidence" class="text-sm font-semibold text-gray-500"></span>
                        </div>

                        <div class="mt-4">
                            <div class="mb-1 flex items-center justify-between text-xs text-gray-500">
                                <span>{{ __('Hold progress') }}</span>
                                <span id="practice-hold-label">0.0s / {{ $practiceConfig['holdSeconds'] }}s</span>
                            </div>
                            <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-200">
                                <div id="practice-hold-bar" class="h-full w-0 bg-teal-500 transition-all duration-300"></div>
                            </div>
                        </div>

                        <div id="practice-message" class="mt-4 rounded-lg bg-gray-50 p-3 text-sm text-gray-600">
                            {{ __('Press Start, then show your') }} {{ $prescription->mudra->name }} {{ __('mudra to the camera.') }}
                        </div>

                        <div class="mt-4">
                            <button id="practice-start" class="w-full rounded-md bg-teal-600 px-4 py-2.5 font-medium text-white hover:bg-teal-700">
                                ▶ {{ __('Start Practice') }}
                            </button>
                            <button id="practice-stop" class="hidden w-full rounded-md border border-gray-300 px-4 py-2.5 font-medium text-gray-700 hover:bg-gray-50">
                                ⏹ {{ __('Stop Practice') }}
                            </button>
                        </div>
                    </x-card>

                    {{-- Target mudra --}}
                    <x-card>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Target Mudra') }}</div>
                        <div class="mt-2 flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-teal-50 text-3xl">{{ $guide['symbol'] }}</div>
                            <div>
                                <div class="font-semibold text-gray-900">{{ $prescription->mudra->name }}</div>
                                <div class="text-xs text-gray-500">{{ $prescription->mudra->description }}</div>
                            </div>
                        </div>
                    </x-card>

                    {{-- Session info --}}
                    <x-card>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Session Info') }}</div>
                        <dl class="mt-2 space-y-1.5 text-sm">
                            <div class="flex justify-between"><dt class="text-gray-500">{{ __('Started at') }}</dt><dd id="practice-session-started" class="font-medium text-gray-700">—</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">{{ __('Today') }}</dt><dd class="font-medium text-gray-700">{{ now()->format('d M Y') }}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">{{ __('Session') }}</dt><dd id="practice-session-id" class="font-medium text-gray-700">—</dd></div>
                        </dl>
                    </x-card>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        @vite('resources/js/practice/practice.js')
    @endpush
</x-app-layout>
