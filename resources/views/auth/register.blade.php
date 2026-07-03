<x-guest-layout wide>
    <div class="mb-6 text-center">
        <h1 class="text-xl font-extrabold tracking-tight text-gray-900">{{ __('Create your patient account') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('Three quick steps and your therapy can begin.') }}</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-8">
        @csrf

        {{-- Step 1 · Account information --}}
        <section aria-labelledby="step-account">
            <div class="mb-4 flex items-center gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-teal-600 text-xs font-bold text-white">1</span>
                <h2 id="step-account" class="font-bold text-gray-900">{{ __('Account Information') }}</h2>
                <span class="h-px flex-1 bg-gray-100"></span>
            </div>

            <div class="space-y-4">
                <div>
                    <x-input-label for="name" :value="__('Full Name')" />
                    <x-text-input id="name" class="mt-1 block w-full" type="text" name="name" :value="old('name')"
                        required autofocus autocomplete="name" placeholder="{{ __('e.g. Meena Kumari') }}"
                        aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')"
                        required autocomplete="username" placeholder="you@example.com"
                        aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="password" :value="__('Password')" />
                        <x-password-input id="password" class="mt-1" autocomplete="new-password" :required="true" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                        <x-password-input id="password_confirmation" class="mt-1" autocomplete="new-password" :required="true" />
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>
                </div>
            </div>
        </section>

        {{-- Step 2 · Doctor selection --}}
        <section aria-labelledby="step-doctor">
            <div class="mb-4 flex items-center gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-teal-600 text-xs font-bold text-white">2</span>
                <h2 id="step-doctor" class="font-bold text-gray-900">{{ __('Doctor Selection') }}</h2>
                <span class="h-px flex-1 bg-gray-100"></span>
            </div>

            <fieldset>
                <legend class="sr-only">{{ __('Choose your doctor') }}</legend>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    @foreach ($doctors as $doctor)
                        <label class="cursor-pointer">
                            <input type="radio" name="doctor_id" value="{{ $doctor->id }}" class="peer sr-only"
                                @checked(old('doctor_id') == $doctor->id) required>
                            <span class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-3 transition
                                peer-checked:border-teal-500 peer-checked:bg-teal-50 peer-checked:ring-1 peer-checked:ring-teal-500
                                peer-focus-visible:ring-2 peer-focus-visible:ring-teal-500 peer-focus-visible:ring-offset-2
                                hover:border-teal-200">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-emerald-500 text-sm font-bold text-white">
                                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($doctor->name, 0, 1)) }}
                                </span>
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-gray-800">{{ $doctor->name }}</span>
                                    <span class="block text-xs text-gray-400">{{ __('Siddha practitioner') }}</span>
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('doctor_id')" class="mt-2" />
            </fieldset>
        </section>

        {{-- Step 3 · Patient information --}}
        <section aria-labelledby="step-patient">
            <div class="mb-4 flex items-center gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-teal-600 text-xs font-bold text-white">3</span>
                <h2 id="step-patient" class="font-bold text-gray-900">{{ __('Patient Information') }}</h2>
                <span class="h-px flex-1 bg-gray-100"></span>
            </div>

            <div class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="age" :value="__('Age')" />
                        <x-text-input id="age" class="mt-1 block w-full" type="number" name="age" :value="old('age')" min="1" max="120"
                            aria-invalid="{{ $errors->has('age') ? 'true' : 'false' }}" />
                        <x-input-error :messages="$errors->get('age')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="gender" :value="__('Gender')" />
                        <select id="gender" name="gender"
                            class="mt-1 block w-full rounded-lg border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 shadow-sm transition focus:border-teal-500 focus:ring-teal-500">
                            <option value="">{{ __('--') }}</option>
                            @foreach (\App\Enums\Gender::cases() as $gender)
                                <option value="{{ $gender->value }}" @selected(old('gender') === $gender->value)>{{ $gender->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('gender')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="phone" :value="__('Phone')" />
                    <x-text-input id="phone" class="mt-1 block w-full" type="text" name="phone" :value="old('phone')" autocomplete="tel"
                        aria-invalid="{{ $errors->has('phone') ? 'true' : 'false' }}" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="condition_notes" :value="__('Condition / Reason for therapy')" />
                    <textarea id="condition_notes" name="condition_notes" rows="3"
                        class="mt-1 block w-full rounded-lg border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 shadow-sm transition placeholder:text-gray-400 focus:border-teal-500 focus:ring-teal-500"
                        placeholder="{{ __('e.g. post-stroke finger stiffness, arthritis…') }}">{{ old('condition_notes') }}</textarea>
                    <p class="mt-1.5 text-xs text-gray-400">{{ __('This helps your doctor tailor the therapy to you.') }}</p>
                    <x-input-error :messages="$errors->get('condition_notes')" class="mt-2" />
                </div>
            </div>
        </section>

        <div class="space-y-4">
            <x-primary-button class="w-full">
                {{ __('Create account') }}
            </x-primary-button>

            <p class="text-center text-sm text-gray-500">
                {{ __('Already have an account?') }}
                <a href="{{ route('login') }}" class="font-semibold text-teal-700 transition hover:text-teal-800">{{ __('Sign in') }}</a>
            </p>
        </div>
    </form>
</x-guest-layout>
