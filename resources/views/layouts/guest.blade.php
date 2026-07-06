@props(['wide' => false])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/logo-icon-dark.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="relative flex min-h-screen flex-col overflow-hidden bg-gradient-to-b from-teal-50/60 via-gray-50 to-gray-50">
            <div class="pointer-events-none absolute -left-32 -top-32 h-80 w-80 rounded-full bg-teal-200/30 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-32 -right-32 h-80 w-80 rounded-full bg-emerald-200/30 blur-3xl"></div>

            <div class="flex flex-1 flex-col items-center justify-center px-6 py-6">
                <a href="/" class="z-10 flex items-center gap-3">
                    <x-application-logo class="h-14 w-14 fill-current text-gray-500" />
                    <span class="text-xl font-bold tracking-tight text-gray-800">{{ config('app.name') }}</span>
                </a>

                <div class="rise-in z-10 my-6 w-full overflow-hidden rounded-2xl bg-white px-6 py-7 shadow-xl shadow-gray-900/5 ring-1 ring-gray-900/5 sm:px-8 {{ $wide ? 'sm:max-w-xl' : 'sm:max-w-md' }}">
                    {{ $slot }}
                </div>
            </div>

            <x-app-footer class="z-10" />
        </div>
    </body>
</html>
