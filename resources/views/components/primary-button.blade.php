<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded-lg border border-transparent bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-teal-600/20 transition duration-150 ease-in-out hover:bg-teal-700 focus:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 active:bg-teal-800 disabled:cursor-not-allowed disabled:opacity-60']) }}>
    {{ $slot }}
</button>
