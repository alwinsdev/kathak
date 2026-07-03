@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-lg border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 shadow-sm transition placeholder:text-gray-400 focus:border-teal-500 focus:ring-teal-500 disabled:cursor-not-allowed disabled:opacity-60']) }}>
