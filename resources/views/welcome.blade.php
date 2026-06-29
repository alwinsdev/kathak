<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-800 antialiased">
    <div class="min-h-screen flex flex-col">
        <header class="px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <x-application-logo class="h-9 w-9" />
                <span class="font-semibold">{{ config('app.name') }}</span>
            </div>
            <nav class="flex items-center gap-3 text-sm">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-md bg-teal-600 px-4 py-2 font-medium text-white hover:bg-teal-700">{{ __('Dashboard') }}</a>
                @else
                    <a href="{{ route('login') }}" class="px-4 py-2 font-medium text-gray-700 hover:text-teal-700">{{ __('Login') }}</a>
                    <a href="{{ route('register') }}" class="rounded-md bg-teal-600 px-4 py-2 font-medium text-white hover:bg-teal-700">{{ __('Register') }}</a>
                @endauth
            </nav>
        </header>

        <main class="flex flex-1 items-center justify-center px-6">
            <div class="max-w-2xl text-center">
                <h1 class="text-4xl font-bold leading-tight text-gray-900">
                    {{ __('Healing through') }}<br>
                    <span class="text-teal-600">{{ __('Siddha Mudras') }}</span>
                </h1>
                <p class="mx-auto mt-5 max-w-xl text-gray-600">
                    {{ __('Doctors prescribe mudra therapy, patients practice it at home, and AI verifies each gesture in real time.') }}
                </p>
                @guest
                    <div class="mt-8 flex justify-center gap-3">
                        <a href="{{ route('login') }}" class="rounded-md bg-teal-600 px-6 py-3 font-medium text-white hover:bg-teal-700">{{ __('Login to Account') }}</a>
                        <a href="{{ route('register') }}" class="rounded-md border border-gray-300 bg-white px-6 py-3 font-medium text-gray-700 hover:border-teal-500">{{ __('Register as Patient') }}</a>
                    </div>
                @endguest
            </div>
        </main>

        <footer class="px-6 py-6 text-center text-xs text-gray-400">
            {{ config('app.name') }} — {{ __('Proof of Concept') }}
        </footer>
    </div>
</body>
</html>
