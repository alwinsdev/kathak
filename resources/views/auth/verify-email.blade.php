<x-guest-layout>
    <div class="mb-6 text-center">
        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-50 text-teal-600">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
        </div>
        <h1 class="text-xl font-extrabold tracking-tight text-gray-900">{{ __('Verify your email') }}</h1>
        <p class="mx-auto mt-1 max-w-sm text-sm text-gray-500">
            {{ __("Thanks for signing up! Please verify your email address by clicking the link we just sent you. Didn't receive it? We can send another.") }}
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <x-auth-session-status class="mb-4" :status="__('A new verification link has been sent to the email address you provided during registration.')" />
    @endif

    <div class="space-y-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button class="w-full">
                {{ __('Resend Verification Email') }}
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="text-center">
            @csrf
            <button type="submit" class="text-sm font-medium text-gray-500 transition hover:text-gray-700">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
