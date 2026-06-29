<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('My Therapy') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-alert type="info">
                {{ __('Welcome,') }} {{ Auth::user()->name }}. {{ __('Foundation (L1) is ready — your therapy schedule and AI practice arrive in modules L3–L4.') }}
            </x-alert>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-stat-card label="Today's Mudras" value="—" icon="🧘" />
                <x-stat-card label="Completed" value="—" icon="✓" />
                <x-stat-card label="Progress" value="—" icon="📊" />
            </div>
        </div>
    </div>
</x-app-layout>
