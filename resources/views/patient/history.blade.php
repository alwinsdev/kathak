<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Practice History') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <x-stat-card label="Total Sessions" :value="$stats->total" icon="📊" />
                <x-stat-card label="This Week" :value="$stats->thisWeek" icon="📆" />
                <x-stat-card label="Current Streak" :value="$stats->streak.' '.__('days')" icon="🔥" />
                <x-stat-card label="Last Practice"
                    :value="$stats->lastPracticeDate?->format('d M Y') ?? '—'" icon="🕒" />
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h3 class="font-semibold text-gray-800">{{ __('Recent Sessions') }}</h3>
                </div>

                @if ($sessions->isEmpty())
                    <div class="px-6 py-12 text-center text-gray-500">
                        <div class="mb-2 text-3xl">📝</div>
                        {{ __('No practice sessions yet.') }}
                        <div class="mt-1 text-sm">{{ __('Verified sessions will appear here once you start practising.') }}</div>
                    </div>
                @else
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-6 py-3 font-semibold">{{ __('Date') }}</th>
                                <th class="px-6 py-3 font-semibold">{{ __('Mudra') }}</th>
                                <th class="px-6 py-3 font-semibold">{{ __('Confidence') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($sessions as $session)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900">{{ $session->practiced_on->format('d M Y') }}</div>
                                        <div class="text-xs text-gray-500">{{ $session->practiced_on->format('l') }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">{{ $session->prescription?->mudra?->name ?? '—' }}</td>
                                    <td class="px-6 py-4 text-gray-600">
                                        {{ $session->best_confidence ? number_format($session->best_confidence * 100, 1).'%' : '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
