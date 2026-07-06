<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('patient.dashboard') }}" class="text-sm text-gray-500 hover:text-teal-700">&larr; {{ __('Back to today') }}</a>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ __('Practice') }}: {{ $prescription->mudra->name }}
                </h2>
                <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-700">
                    {{ __('Prescribed duration') }}: {{ $prescription->duration_min }} {{ __('min') }} · {{ __('Confidence') }} ≥ {{ (int) round($practiceConfig['confidenceThreshold'] * 100) }}%
                </span>
            </div>
            <a href="#mudra-guide" class="inline-flex items-center gap-1.5 text-sm font-medium text-teal-700 hover:text-teal-800">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                {{ __('Need help?') }}
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div @unless ($completedToday) id="practice-root" @endunless
             class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
             data-start-url="{{ route('patient.practice.start', $prescription) }}"
             data-detect-template="{{ route('patient.practice.detect', ['session' => '__SESSION__']) }}"
             data-target="{{ $prescription->mudra->name }}"
             data-target-label="{{ $prescription->mudra->ai_class_label }}"
             data-confidence-threshold="{{ $practiceConfig['confidenceThreshold'] }}"
             data-next-url="{{ $nextPractice ? route('patient.practice.show', $nextPractice) : '' }}"
             data-next-name="{{ $nextPractice?->mudra->name ?? '' }}"
             data-jpeg-quality="{{ $practiceConfig['jpegQuality'] }}"
             data-hold-seconds="{{ $practiceConfig['holdSeconds'] }}"
             data-detection-interval-ms="{{ $practiceConfig['detectionIntervalMs'] }}">

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                {{-- LEFT: camera + guide --}}
                <div class="space-y-6 lg:col-span-2">
                    @if ($completedToday)
                        {{-- Already done today: calm completed state, no camera --}}
                        <div class="flex flex-col items-center justify-center rounded-xl border border-teal-100 bg-gradient-to-b from-teal-50 to-white px-6 py-16 text-center shadow-sm" style="min-height:320px">
                            <div class="flex h-20 w-20 items-center justify-center rounded-full bg-teal-100 text-teal-600">
                                <svg class="h-11 w-11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            </div>
                            <h3 class="mt-5 text-xl font-semibold text-gray-900">{{ __('Completed for today') }}</h3>
                            <p class="mt-1 max-w-sm text-sm text-gray-500">
                                {{ __('You have already practised') }} <span class="font-medium text-gray-700">{{ $prescription->mudra->name }}</span> {{ __('today. Great work — come back tomorrow!') }}
                            </p>
                        </div>
                    @else
                    {{-- Camera --}}
                    <div class="relative overflow-hidden rounded-2xl bg-gray-900 shadow-sm ring-1 ring-gray-900/5" style="min-height:320px">
                        <video id="practice-video" autoplay playsinline muted class="block w-full"></video>
                        <canvas id="practice-overlay" class="pointer-events-none absolute inset-0 h-full w-full"></canvas>

                        {{-- Pre-start designed state (hidden once the camera runs) --}}
                        <div id="practice-idle" class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-gradient-to-br from-gray-800 to-gray-900 px-6 text-center">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10 text-white/70">
                                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                            </div>
                            <p class="text-sm font-semibold text-white/80">{{ __('Your camera preview will appear here') }}</p>
                            <p class="text-xs text-white/40">{{ __('Press Start Practice, then mirror the target mudra.') }}</p>
                        </div>

                        <div id="practice-camera-pill"
                             class="absolute left-3 top-3 hidden items-center gap-1.5 rounded-full bg-black/55 px-3 py-1 text-xs font-medium text-white backdrop-blur">
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-70"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-400"></span>
                            </span>
                            {{ __('Camera Active') }}
                        </div>
                        <div id="practice-resolution"
                             class="absolute bottom-4 right-3 rounded bg-black/55 px-2 py-0.5 text-xs text-white/90"></div>

                        {{-- Live detection pill (mirrors the status card; decorative for AT) --}}
                        <div id="practice-live-pill" aria-hidden="true"
                             class="absolute bottom-4 left-3 hidden items-center gap-2 rounded-full bg-gray-900/75 px-3.5 py-1.5 text-xs font-semibold text-white backdrop-blur">
                            <span id="practice-live-dot" class="h-2 w-2 rounded-full bg-gray-400"></span>
                            <span id="practice-live-text">{{ __('Searching…') }}</span>
                        </div>

                        {{-- Hold progress strip on the video edge --}}
                        <div class="absolute inset-x-0 bottom-0 h-1.5 bg-black/40">
                            <div id="practice-hold-strip" class="h-full w-0 bg-teal-400 transition-all duration-300"></div>
                        </div>

                        {{-- Success celebration overlay (revealed by practice.js on verification) --}}
                        <div id="practice-success" class="absolute inset-0 z-10 hidden flex-col items-center justify-center bg-teal-900/75 px-6 text-center backdrop-blur-sm">
                            <div id="practice-confetti" class="pointer-events-none absolute inset-0 overflow-hidden"></div>
                            <div class="practice-pop flex h-20 w-20 items-center justify-center rounded-full bg-white text-teal-600 shadow-xl">
                                <svg class="h-11 w-11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            </div>
                            <h3 class="mt-4 text-2xl font-extrabold text-white">{{ __('Session verified!') }}</h3>
                            <p id="practice-success-sub" class="mt-1 text-sm text-teal-100"></p>
                            <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                                <a id="practice-next" href="#"
                                   class="hidden items-center gap-1.5 rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-teal-700 shadow-lg transition hover:-translate-y-0.5">
                                    {{ __('Next:') }} <span id="practice-next-name"></span>
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                                </a>
                                <a href="{{ route('patient.dashboard') }}"
                                   class="rounded-xl border border-white/40 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-white/10">
                                    {{ __('Back to today') }}
                                </a>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Mudra teaching guide --}}
                    <x-card id="mudra-guide">
                        <h3 class="mb-4 font-semibold text-gray-800">{{ __('How to do') }} {{ $prescription->mudra->name }} {{ __('mudra') }}</h3>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Steps') }}</div>
                                <ol class="mt-3 space-y-3 text-sm text-gray-700">
                                    @foreach ($guide['steps'] as $i => $step)
                                        <li class="flex items-start gap-3">
                                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-teal-600 text-xs font-semibold text-white mt-1">{{ $i + 1 }}</span>
                                            @if (isset($guide['step_images'][$i]))
                                                <img src="{{ asset($guide['step_images'][$i]) }}" alt="Step {{ $i + 1 }}" class="h-14 w-14 rounded-lg object-cover border border-gray-200 shadow-sm shrink-0">
                                            @endif
                                            <div class="leading-snug">
                                                @if (is_array($step))
                                                    <strong class="font-semibold text-gray-900 block">{{ $step['title'] }}</strong>
                                                    <span class="text-xs text-gray-500 mt-0.5 block">{{ $step['description'] }}</span>
                                                @else
                                                    <span>{{ $step }}</span>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ol>
                            </div>
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Common mistakes') }}</div>
                                <ul class="mt-2 space-y-2 text-sm text-gray-700">
                                    @foreach ($guide['mistakes'] as $mistake)
                                        <li class="flex gap-2"><span class="text-rose-500 font-bold">✗</span><span>{{ $mistake }}</span></li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>

                        @if (isset($guide['before_start']) || isset($guide['tips']) || isset($guide['duration']))
                            <hr class="my-6 border-gray-150">
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                                @if (isset($guide['before_start']))
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Before You Start') }}</div>
                                        <ul class="mt-2 space-y-1.5 text-sm text-gray-700">
                                            @foreach ($guide['before_start'] as $item)
                                                <li class="flex gap-2"><span class="text-teal-500 font-medium">✓</span><span>{{ $item }}</span></li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                
                                @if (isset($guide['tips']))
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Important Tips') }}</div>
                                        <ul class="mt-2 space-y-1.5 text-sm text-gray-700">
                                            @foreach ($guide['tips'] as $item)
                                                <li class="flex gap-2"><span class="text-amber-500">💡</span><span>{{ $item }}</span></li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Practice Duration') }}</div>
                                    <div class="mt-2 rounded-xl bg-teal-50/50 p-3.5 border border-teal-100/50 text-sm text-gray-700 leading-relaxed space-y-2">
                                        <div class="flex items-center gap-2">
                                            <svg class="h-4 w-4 shrink-0 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            <span>{{ __('Practice for') }} <strong class="text-gray-900">{{ $practiceConfig['durationMin'] }} {{ __('minutes') }}</strong> {{ __('per session') }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <svg class="h-4 w-4 shrink-0 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                            <span>{{ __('Hold the mudra steady in front of the camera to verify completion') }}</span>
                                        </div>
                                        <p class="text-xs text-gray-500 italic">{{ __('Duration prescribed by your doctor.') }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </x-card>
                </div>

                {{-- RIGHT: detection, progress, session --}}
                <div class="space-y-6">
                    {{-- Detection status --}}
                    @if ($completedToday)
                        <x-card>
                            <h3 class="font-semibold text-gray-800">{{ __('Detection Status') }}</h3>

                            <div class="mt-3 flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-full bg-teal-500"></span>
                                <span class="text-sm font-semibold text-teal-700">{{ __('Completed') }}</span>
                                @if ($completedToday->best_confidence)
                                    <span class="ml-auto text-sm font-semibold text-gray-500">{{ number_format($completedToday->best_confidence * 100, 0) }}%</span>
                                @endif
                            </div>

                            <div class="mt-4 h-2.5 w-full overflow-hidden rounded-full bg-gray-200">
                                <div class="h-full w-full bg-teal-500"></div>
                            </div>

                            <div class="mt-4 rounded-lg bg-teal-50 p-3 text-sm text-teal-800">
                                ✓ {{ __('Verified today') }}@if ($completedToday->completed_at) {{ __('at') }} {{ $completedToday->completed_at->format('g:i A') }}@endif. {{ __('Great work!') }}
                            </div>

                            <a href="{{ route('patient.dashboard') }}"
                               class="mt-4 block w-full rounded-xl bg-teal-600 px-4 py-2.5 text-center font-semibold text-white shadow-sm shadow-teal-600/20 transition hover:bg-teal-700">
                                {{ __('Back to today') }}
                            </a>
                        </x-card>
                    @else
                    <x-card>
                        <h3 class="font-semibold text-gray-800">{{ __('Detection Status') }}</h3>

                        {{-- State box: friendly tier + big confidence number --}}
                        <div id="practice-state" class="mt-3 flex items-center justify-between gap-3 rounded-xl bg-gray-50 px-3.5 py-3 transition-colors">
                            <div class="flex min-w-0 items-center gap-2.5">
                                <span id="practice-status-dot" class="h-2.5 w-2.5 shrink-0 rounded-full bg-gray-300"></span>
                                <span id="practice-detected" class="truncate text-sm font-semibold text-gray-700">{{ __('Press Start to begin') }}</span>
                            </div>
                            <span id="practice-confidence" class="shrink-0 text-xl font-extrabold tabular-nums text-gray-900"></span>
                        </div>

                        {{-- Detected vs target (shown only on a mismatch) --}}
                        <div id="practice-compare" class="mt-2 hidden items-center justify-between gap-2 rounded-xl border border-rose-100 bg-rose-50 px-3.5 py-2 text-xs">
                            <span class="min-w-0 truncate text-rose-700">{{ __('Detected') }}: <b id="practice-compare-detected" class="font-bold"></b></span>
                            <svg class="h-3.5 w-3.5 shrink-0 text-rose-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                            <span class="min-w-0 truncate text-gray-600">{{ __('Target') }}: <b class="font-bold text-gray-800">{{ $prescription->mudra->name }}</b></span>
                        </div>

                        <div class="mt-4">
                            <div class="mb-1.5 flex items-center justify-between text-xs text-gray-500">
                                <span class="font-medium">{{ __('Verification progress') }}</span>
                                <span id="practice-hold-label" class="font-semibold tabular-nums">0.0s / {{ $practiceConfig['holdSeconds'] }}s</span>
                            </div>
                            <div class="h-3 w-full overflow-hidden rounded-full bg-gray-100 ring-1 ring-inset ring-gray-900/5">
                                <div id="practice-hold-bar" class="h-full w-0 rounded-full bg-gradient-to-r from-teal-500 to-emerald-400 transition-all duration-300"></div>
                            </div>
                        </div>

                        <div id="practice-message" aria-live="polite" class="mt-4 rounded-xl bg-gray-50 p-3 text-sm text-gray-600">
                            {{ __('Press Start, then show your') }} {{ $prescription->mudra->name }} {{ __('mudra to the camera.') }}
                        </div>

                        <div class="mt-4">
                            <button id="practice-start" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-teal-600 px-4 py-2.5 font-semibold text-white shadow-sm shadow-teal-600/20 transition hover:bg-teal-700">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z" /></svg>
                                {{ __('Start Practice') }}
                            </button>
                            <button id="practice-stop" class="hidden inline-flex w-full items-center justify-center gap-2 rounded-xl border border-gray-200 px-4 py-2.5 font-semibold text-gray-700 transition hover:bg-gray-50">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="6" width="12" height="12" rx="1.5" /></svg>
                                {{ __('Stop Practice') }}
                            </button>
                        </div>
                    </x-card>
                    @endif

                    {{-- Target mudra --}}
                    <x-card>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Target Mudra') }}</div>
                        <div class="mt-3 flex items-center gap-4">
                            <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-teal-50 text-3xl ring-1 ring-gray-900/5">
                                @if ($prescription->mudra->reference_image_path)
                                    <img src="{{ asset($prescription->mudra->reference_image_path) }}" alt="{{ $prescription->mudra->name }}" class="h-full w-full object-cover">
                                @else
                                    {{ $guide['symbol'] }}
                                @endif
                            </div>
                            <div class="min-w-0">
                                <div class="font-bold text-gray-900">{{ $prescription->mudra->name }}</div>
                                <div class="mt-0.5 text-xs leading-relaxed text-gray-500">{{ $prescription->mudra->description }}</div>
                                <a href="#mudra-guide" class="mt-1.5 inline-flex items-center gap-1 text-xs font-semibold text-teal-700 hover:text-teal-800">
                                    {{ __('See steps') }}
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                                </a>
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
