<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
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

        <div class="flex min-h-screen flex-col">
            {{-- Header --}}
            <header class="sticky top-0 z-10 border-b border-gray-100/80 bg-white/70 backdrop-blur">
                <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                    <div class="flex items-center gap-3">
                        <x-application-logo class="h-9 w-9" />
                        <span class="text-lg font-bold tracking-tight text-gray-900">{{ config('app.name') }}</span>
                    </div>
                    <nav class="flex items-center gap-2 text-sm">
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-lg bg-teal-600 px-4 py-2 font-semibold text-white shadow-sm shadow-teal-600/20 transition hover:bg-teal-700">{{ __('Dashboard') }}</a>
                        @else
                            <a href="{{ route('login') }}" class="rounded-lg px-4 py-2 font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900">{{ __('Login') }}</a>
                            <a href="{{ route('register') }}" class="rounded-lg bg-teal-600 px-4 py-2 font-semibold text-white shadow-sm shadow-teal-600/20 transition hover:bg-teal-700">{{ __('Register') }}</a>
                        @endauth
                    </nav>
                </div>
            </header>

            {{-- Hero --}}
            <main class="mx-auto flex w-full max-w-6xl flex-1 flex-col items-center px-6 py-16 text-center sm:py-24">
                <span class="inline-flex items-center gap-2 rounded-full border border-teal-200 bg-teal-50 px-4 py-1.5 text-xs font-semibold text-teal-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-teal-500"></span>
                    {{ __('AI-verified mudra therapy') }}
                </span>

                <h1 class="mt-6 text-4xl font-extrabold leading-[1.1] tracking-tight text-gray-900 sm:text-6xl">
                    {{ __('Healing through') }}<br>
                    <span class="bg-gradient-to-r from-teal-600 to-emerald-500 bg-clip-text text-transparent">{{ __('Siddha Mudras') }}</span>
                </h1>

                <p class="mx-auto mt-6 max-w-xl text-lg leading-relaxed text-gray-600">
                    {{ __('Doctors prescribe mudra therapy, patients practise it at home, and AI verifies each gesture in real time.') }}
                </p>

                @guest
                    <div class="mt-9 flex flex-col items-center gap-3 sm:flex-row">
                        <a href="{{ route('login') }}"
                           class="w-full rounded-xl bg-teal-600 px-7 py-3.5 font-semibold text-white shadow-lg shadow-teal-600/25 transition hover:-translate-y-0.5 hover:bg-teal-700 sm:w-auto">
                            {{ __('Login to Account') }}
                        </a>
                        <a href="{{ route('register') }}"
                           class="w-full rounded-xl border border-gray-200 bg-white px-7 py-3.5 font-semibold text-gray-700 shadow-sm transition hover:-translate-y-0.5 hover:border-teal-300 hover:text-teal-700 sm:w-auto">
                            {{ __('Register as Patient') }}
                        </a>
                    </div>
                @endguest

                {{-- Feature highlights --}}
                <div class="mt-20 grid w-full max-w-4xl grid-cols-1 gap-5 text-left sm:grid-cols-3">
                    @php
                        $features = [
                            ['t' => __('Prescribe'), 'd' => __('Doctors assign mudras with a schedule, duration and notes — tailored per patient.'), 'p' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
                            ['t' => __('Practise'), 'd' => __('Patients follow clear guidance and practise each gesture from home, on any camera.'), 'p' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'],
                            ['t' => __('AI verifies'), 'd' => __('A self-hosted model recognises the mudra live and auto-completes once held steady.'), 'p' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ];
                    @endphp
                    @foreach ($features as $f)
                        <div class="rounded-2xl border border-gray-100 bg-white/80 p-6 shadow-sm ring-1 ring-gray-900/[0.03] backdrop-blur">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-teal-500 to-emerald-500 text-white shadow-sm shadow-teal-600/30">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $f['p'] }}" /></svg>
                            </div>
                            <h3 class="mt-4 font-bold text-gray-900">{{ $f['t'] }}</h3>
                            <p class="mt-1.5 text-sm leading-relaxed text-gray-500">{{ $f['d'] }}</p>
                        </div>
                    @endforeach
                </div>
            </main>

            <footer class="border-t border-gray-100 px-6 py-6 text-center text-xs text-gray-400">
                {{ config('app.name') }} — {{ __('Proof of Concept') }}
            </footer>
        </div>
    </div>
</body>
</html>
