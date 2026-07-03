<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Account Settings') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Profile summary --}}
            <div class="rise-in overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm ring-1 ring-gray-900/[0.03]">
                <div class="flex flex-col gap-5 p-6 sm:flex-row sm:items-center sm:justify-between sm:p-7">
                    <div class="flex min-w-0 items-center gap-4">
                        <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-500 to-emerald-500 text-2xl font-extrabold text-white shadow-md shadow-teal-600/25">
                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->name, 0, 1)) }}
                        </span>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="truncate text-2xl font-extrabold tracking-tight text-gray-900">{{ $user->name }}</h3>
                                <span class="rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-bold uppercase tracking-wide text-teal-700 ring-1 ring-teal-100">
                                    {{ $user->isPatient() ? __('Patient') : __('Doctor') }}
                                </span>
                            </div>
                            <p class="mt-0.5 truncate text-sm text-gray-500">{{ $user->email }}</p>

                            @if ($user->isPatient() && $user->patientProfile)
                                <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                                    @if ($user->patientProfile->doctor)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-50 px-2.5 py-1 font-medium text-gray-600 ring-1 ring-gray-100">
                                            <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                            {{ $user->patientProfile->doctor->name }}
                                        </span>
                                    @endif
                                    @if ($user->patientProfile->condition_notes)
                                        <span class="inline-flex max-w-full items-center gap-1.5 truncate rounded-full bg-amber-50 px-2.5 py-1 font-medium text-amber-700 ring-1 ring-amber-100">
                                            <svg class="h-3.5 w-3.5 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                                            <span class="truncate">{{ $user->patientProfile->condition_notes }}</span>
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="shrink-0 text-sm text-gray-400 sm:text-right">
                        <div class="text-[11px] font-semibold uppercase tracking-wide">{{ __('Member since') }}</div>
                        <div class="mt-0.5 font-bold tabular-nums text-gray-700">{{ $user->created_at->format('d M Y') }}</div>
                    </div>
                </div>
            </div>

            {{-- Forms --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="rise-in-1 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm ring-1 ring-gray-900/[0.03]">
                    @include('profile.partials.update-profile-information-form')
                </div>

                <div class="rise-in-2 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm ring-1 ring-gray-900/[0.03]">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            {{-- Danger zone --}}
            <div class="rise-in-3 rounded-2xl border border-rose-100 bg-white p-6 shadow-sm ring-1 ring-rose-100/60">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
