<x-guest-layout>
    <div class="mb-6 text-center">
        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-50 text-teal-600">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
        </div>
        <h1 class="text-xl font-extrabold tracking-tight text-gray-900">{{ __('Forgot your password?') }}</h1>
        <p class="mx-auto mt-1 max-w-xs text-sm text-gray-500">{{ __("No problem — enter your email and we'll send you a link to choose a new one.") }}</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')"
                required autofocus placeholder="you@example.com"
                aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <x-primary-button class="w-full">
            {{ __('Email Password Reset Link') }}
        </x-primary-button>

        <p class="text-center text-sm text-gray-500">
            <a href="{{ route('login') }}" class="inline-flex items-center gap-1 font-medium text-teal-700 transition hover:text-teal-800">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                {{ __('Back to login') }}
            </a>
        </p>
    </form>
</x-guest-layout>
