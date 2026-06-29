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
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center shadow-sm">
                <div class="mb-3 text-5xl">📷</div>
                <h3 class="text-lg font-semibold text-gray-800">{{ __('Live AI Practice — coming in L4') }}</h3>
                <p class="mx-auto mt-2 max-w-md text-sm text-gray-500">
                    {{ __('This is where your camera will open and the AI will verify that you are performing the') }}
                    <strong>{{ $prescription->mudra->name }}</strong>
                    {{ __('mudra correctly. Once verified, the session is recorded automatically.') }}
                </p>
                <div class="mt-6">
                    <a href="{{ route('patient.prescriptions.show', $prescription) }}"
                       class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:border-teal-500">
                        {{ __('View prescription details') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
