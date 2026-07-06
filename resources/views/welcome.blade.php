<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/logo-icon-dark.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans text-gray-800 antialiased">
    <div class="relative min-h-screen overflow-hidden">
        {{-- soft ambient background --}}
        <div class="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-b from-teal-50/70 via-white to-white"></div>
        <div class="pointer-events-none absolute -left-40 -top-40 -z-10 h-96 w-96 rounded-full bg-teal-200/30 blur-3xl"></div>
        <div class="pointer-events-none absolute -right-40 top-20 -z-10 h-96 w-96 rounded-full bg-emerald-200/30 blur-3xl"></div>

        {{-- Header --}}
        <header class="sticky top-0 z-10 border-b border-gray-100/80 bg-white/70 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                <div class="flex items-center gap-3">
                    <x-application-logo class="h-9 w-9" />
                    <span class="text-lg font-bold tracking-tight text-gray-900">{{ config('app.name') }}</span>
                </div>
                <nav class="flex items-center gap-2 text-sm" aria-label="{{ __('Primary') }}">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-lg bg-teal-600 px-4 py-2 font-semibold text-white shadow-sm shadow-teal-600/20 transition hover:bg-teal-700">{{ __('Dashboard') }}</a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-lg px-4 py-2 font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900">{{ __('Login') }}</a>
                        <a href="{{ route('register') }}" class="rounded-lg bg-teal-600 px-4 py-2 font-semibold text-white shadow-sm shadow-teal-600/20 transition hover:bg-teal-700">{{ __('Register') }}</a>
                    @endauth
                </nav>
            </div>
        </header>

        <main>
            {{-- Hero --}}
            <section class="mx-auto grid w-full max-w-6xl items-center gap-12 px-6 py-16 sm:py-20 lg:grid-cols-2" aria-labelledby="hero-title">
                <div class="rise-in text-center lg:text-left">
                    <span class="inline-flex items-center gap-2 rounded-full border border-teal-200 bg-teal-50 px-4 py-1.5 text-xs font-semibold text-teal-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-teal-500"></span>
                        {{ __('AI-verified mudra therapy') }}
                    </span>

                    <h1 id="hero-title" class="mt-6 text-4xl font-extrabold leading-[1.1] tracking-tight text-gray-900 sm:text-5xl">
                        {{ __('Healing through') }}<br>
                        <span class="bg-gradient-to-r from-teal-600 to-emerald-500 bg-clip-text text-transparent">{{ __('Siddha Mudras') }}</span>
                    </h1>

                    <p class="mx-auto mt-5 max-w-xl text-lg leading-relaxed text-gray-600 lg:mx-0">
                        {{ __('Your doctor prescribes traditional hand mudras. You practise them at home in front of your camera, and the app verifies each gesture and records your progress.') }}
                    </p>

                    @guest
                        <div class="mt-8 flex flex-col items-center gap-3 sm:flex-row lg:justify-start sm:justify-center">
                            <a href="{{ route('register') }}"
                               class="w-full rounded-xl bg-teal-600 px-7 py-3.5 text-center font-semibold text-white shadow-lg shadow-teal-600/25 transition hover:-translate-y-0.5 hover:bg-teal-700 sm:w-auto">
                                {{ __('Register as Patient') }}
                            </a>
                            <a href="{{ route('login') }}"
                               class="w-full rounded-xl border border-gray-200 bg-white px-7 py-3.5 text-center font-semibold text-gray-700 shadow-sm transition hover:-translate-y-0.5 hover:border-teal-300 hover:text-teal-700 sm:w-auto">
                                {{ __('Login to Account') }}
                            </a>
                        </div>
                    @endguest
                </div>

                {{-- Product preview: a faithful miniature of the real practice screen --}}
                <div class="rise-in-1 mx-auto w-full max-w-sm" aria-hidden="true">
                    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-xl shadow-gray-900/10 ring-1 ring-gray-900/5">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-800">{{ __('Detection Status') }}</h3>
                            <span class="text-lg font-extrabold tabular-nums text-gray-900">97%</span>
                        </div>

                        <div class="mt-3 flex items-center gap-2.5 rounded-xl bg-teal-50 px-3.5 py-2.5">
                            <span class="h-2.5 w-2.5 rounded-full bg-teal-500"></span>
                            <span class="text-sm font-semibold text-teal-700">{{ __('Good — Soochi') }}</span>
                        </div>

                        <div class="mt-4">
                            <div class="mb-1.5 flex items-center justify-between text-xs text-gray-500">
                                <span class="font-medium">{{ __('Hold progress') }}</span>
                                <span class="font-semibold tabular-nums">2.1s / 3s</span>
                            </div>
                            <div class="h-3 w-full overflow-hidden rounded-full bg-gray-100 ring-1 ring-inset ring-gray-900/5">
                                <div class="demo-hold h-full rounded-full bg-gradient-to-r from-teal-500 to-emerald-400"></div>
                            </div>
                        </div>

                        <div class="mt-4 rounded-xl bg-gray-50 p-3 text-sm text-gray-600">
                            {{ __('Great! Keep holding… steady hands are the key.') }}
                        </div>

                        <div class="mt-4 flex items-center gap-3 rounded-xl border border-gray-100 p-3">
                            <img src="{{ asset('images/mudras/aakash.jpg') }}" alt="" class="h-12 w-12 rounded-lg object-cover ring-1 ring-gray-900/5">
                            <div class="min-w-0">
                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Target Mudra') }}</div>
                                <div class="text-sm font-bold text-gray-900">Soochi</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- How it works --}}
            <section class="mx-auto w-full max-w-6xl px-6 py-14" aria-labelledby="how-title">
                <h2 id="how-title" class="text-center text-2xl font-extrabold tracking-tight text-gray-900">{{ __('How it works') }}</h2>
                <p class="mx-auto mt-2 max-w-lg text-center text-gray-500">{{ __('One simple loop, from prescription to verified progress.') }}</p>

                <ol class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-5">
                    @php
                        $steps = [
                            ['t' => __('Doctor'), 'd' => __('Your doctor reviews your condition.'), 'p' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                            ['t' => __('Prescription'), 'd' => __('Mudras are prescribed with a time, duration and notes.'), 'p' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
                            ['t' => __('Practice'), 'd' => __('You practise at home with photo and step-by-step guidance.'), 'p' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'],
                            ['t' => __('AI Verification'), 'd' => __('The gesture is recognised live and verified once held steady.'), 'p' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                            ['t' => __('Progress'), 'd' => __('Sessions are recorded on your calendar; your doctor sees adherence.'), 'p' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h10a2 2 0 012 2v12a2 2 0 01-2 2z'],
                        ];
                    @endphp
                    @foreach ($steps as $i => $step)
                        <li class="relative rounded-2xl border border-gray-100 bg-white p-5 text-center shadow-sm ring-1 ring-gray-900/[0.03]">
                            <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-teal-500 to-emerald-500 text-white shadow-sm shadow-teal-600/30">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $step['p'] }}" /></svg>
                            </div>
                            <div class="mt-3 text-xs font-bold uppercase tracking-wide text-teal-600">{{ __('Step') }} {{ $i + 1 }}</div>
                            <h3 class="mt-1 font-bold text-gray-900">{{ $step['t'] }}</h3>
                            <p class="mt-1.5 text-sm leading-relaxed text-gray-500">{{ $step['d'] }}</p>
                        </li>
                    @endforeach
                </ol>
            </section>

            {{-- Features (all shipped) --}}
            <section class="mx-auto w-full max-w-6xl px-6 py-14" aria-labelledby="features-title">
                <h2 id="features-title" class="text-center text-2xl font-extrabold tracking-tight text-gray-900">{{ __('What the app does today') }}</h2>

                <div class="mt-10 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @php
                        $features = [
                            ['t' => __('AI Mudra Recognition'), 'd' => __('A self-hosted model recognises your hand gesture live from the camera.'), 'p' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                            ['t' => __('Guided Practice'), 'd' => __('Real mudra photos, steps and common mistakes for every prescription.'), 'p' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
                            ['t' => __('Progress Calendar'), 'd' => __('A practice heatmap with streaks and per-day session details.'), 'p' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                            ['t' => __('Doctor Monitoring'), 'd' => __('Doctors see 7-day adherence and completed sessions for each patient.'), 'p' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                        ];
                    @endphp
                    @foreach ($features as $f)
                        <div class="rounded-2xl border border-gray-100 bg-white/80 p-6 shadow-sm ring-1 ring-gray-900/[0.03] backdrop-blur transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-teal-500 to-emerald-500 text-white shadow-sm shadow-teal-600/30">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $f['p'] }}" /></svg>
                            </div>
                            <h3 class="mt-4 font-bold text-gray-900">{{ $f['t'] }}</h3>
                            <p class="mt-1.5 text-sm leading-relaxed text-gray-500">{{ $f['d'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Benefits --}}
            <section class="mx-auto w-full max-w-6xl px-6 py-6" aria-labelledby="benefits-title">
                <div class="rounded-3xl bg-gradient-to-r from-teal-600 to-emerald-600 px-8 py-10 text-white shadow-xl shadow-teal-600/20">
                    <h2 id="benefits-title" class="text-center text-xl font-extrabold tracking-tight">{{ __('Why it helps') }}</h2>
                    <div class="mt-6 grid grid-cols-1 gap-6 text-center sm:grid-cols-3">
                        @php
                            $benefits = [
                                ['t' => __('Therapy at home'), 'd' => __('Practise on your own schedule — only a webcam is needed.')],
                                ['t' => __('Objective verification'), 'd' => __('Sessions complete only when the gesture is held correctly.')],
                                ['t' => __('Steady motivation'), 'd' => __('Streaks and a visible calendar make consistency easier.')],
                            ];
                        @endphp
                        @foreach ($benefits as $b)
                            <div>
                                <div class="mx-auto flex h-9 w-9 items-center justify-center rounded-full bg-white/15">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                </div>
                                <h3 class="mt-3 font-bold">{{ $b['t'] }}</h3>
                                <p class="mt-1 text-sm leading-relaxed text-teal-50/90">{{ $b['d'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

        </main>

        {{-- Footer --}}
        <footer class="border-t border-gray-100 bg-white/60">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-6 py-6 text-sm text-gray-500 sm:flex-row">
                <p>
                    {{ __('Developed by') }}
                    <a href="https://redmindtechnologies.com/" target="_blank" rel="noopener"
                       class="font-bold text-gray-800 transition hover:opacity-75">
                        <span class="text-red-600">R</span>ed<span class="text-red-600">M</span>ind Technologies
                    </a>
                </p>

                <a href="mailto:support@redmindtechnologies.com"
                   class="inline-flex items-center gap-1.5 font-medium transition hover:text-teal-700">
                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    support@redmindtechnologies.com
                </a>

                <p class="text-gray-400">
                    © {{ now()->year }} {{ config('app.name') }} · {{ __('Version 1.0.0') }} · {{ __('Proof of Concept') }}
                </p>
            </div>
        </footer>
    </div>
</body>
</html>
