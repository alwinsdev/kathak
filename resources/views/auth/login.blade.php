<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-xl font-extrabold tracking-tight text-gray-900">{{ __('Welcome back') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('Sign in to continue your therapy journey.') }}</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')"
                required autofocus autocomplete="username" placeholder="you@example.com"
                aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" :value="__('Password')" />
                @if (Route::has('password.request'))
                    <a class="text-sm font-medium text-teal-700 transition hover:text-teal-800" href="{{ route('password.request') }}">
                        {{ __('Forgot password?') }}
                    </a>
                @endif
            </div>
            <x-password-input id="password" class="mt-1" autocomplete="current-password" :required="true" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <label for="remember_me" class="inline-flex cursor-pointer items-center">
            <input id="remember_me" type="checkbox" name="remember"
                class="rounded border-gray-300 text-teal-600 shadow-sm focus:ring-teal-500">
            <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
        </label>

        <x-primary-button class="w-full">
            {{ __('Log in') }}
        </x-primary-button>

        <p class="text-center text-sm text-gray-500">
            {{ __('New here?') }}
            <a href="{{ route('register') }}" class="font-semibold text-teal-700 transition hover:text-teal-800">{{ __('Register as a patient') }}</a>
        </p>
    </form>
</x-guest-layout>
