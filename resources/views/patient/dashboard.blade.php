<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('My Therapy') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-stat-card label="Today's Therapy" :value="$today->summary->total" icon="🧘" />
                <x-stat-card label="Completed" :value="$today->summary->completed" icon="✓" />
                <x-stat-card label="Pending" :value="$today->summary->pending" icon="⏳" />
            </div>

            <x-card>
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800">{{ __("Today's Therapy") }}</h3>
                    <span class="text-sm text-gray-500">{{ now()->format('l, d M Y') }}</span>
                </div>

                @if ($today->mudras->isEmpty())
                    <div class="py-12 text-center text-gray-500">
                        <div class="mb-2 text-3xl">📋</div>
                        {{ __('No therapy scheduled for today.') }}
                        <div class="mt-1 text-sm">{{ __('Your doctor will prescribe a routine soon.') }}</div>
                    </div>
                @else
                    <ul class="space-y-3">
                        @foreach ($today->mudras as $item)
                            @php($prescription = $item->prescription)
                            <li class="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-gray-200 p-4">
                                <div class="flex items-center gap-4">
                                    <div class="text-center">
                                        <div class="text-lg font-semibold text-teal-700">
                                            {{ \Illuminate\Support\Str::substr($prescription->scheduled_time, 0, 5) }}
                                        </div>
                                        <div class="text-xs text-gray-400">{{ $prescription->duration_min }} {{ __('min') }}</div>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900">{{ $prescription->mudra->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $prescription->mudra->description }}</div>
                                        @if ($prescription->notes)
                                            <div class="mt-1 text-xs text-teal-700">📝 {{ $prescription->notes }}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center gap-3">
                                    @if ($item->completedToday)
                                        <x-badge color="green">✓ {{ __('Done') }}</x-badge>
                                    @else
                                        <x-badge color="orange">{{ __('Pending') }}</x-badge>
                                    @endif

                                    <a href="{{ route('patient.prescriptions.show', $prescription) }}"
                                       class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:border-teal-500">
                                        {{ __('Details') }}
                                    </a>
                                    <a href="{{ route('patient.practice.show', $prescription) }}"
                                       class="rounded-md bg-teal-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-teal-700">
                                        📷 {{ __('Practice') }}
                                    </a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
