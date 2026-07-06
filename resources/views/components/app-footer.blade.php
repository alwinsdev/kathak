{{-- Shared application footer: developer credit · support · version. --}}
<footer {{ $attributes->merge(['class' => 'border-t border-gray-100 bg-white/60']) }}>
    <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2.5 px-4 py-5 text-sm text-gray-500 sm:flex-row sm:px-6 lg:px-8">
        <p>
            {{ __('Developed by') }}
            <a href="https://redmindtechnologies.com/" target="_blank" rel="noopener"
               class="font-bold text-gray-800 transition hover:opacity-75">
                <span class="text-red-600">R</span>ed<span class="text-red-600">M</span>ind Technologies
            </a>
        </p>

        <a href="mailto:support@redmindtechnologies.com"
           class="inline-flex items-center gap-1.5 font-medium transition hover:text-teal-700">
            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
            support@redmindtechnologies.com
        </a>

        <p class="text-gray-400">
            © {{ now()->year }} {{ config('app.name') }} · {{ __('Version 1.0.0') }} · {{ __('Proof of Concept') }}
        </p>
    </div>
</footer>
