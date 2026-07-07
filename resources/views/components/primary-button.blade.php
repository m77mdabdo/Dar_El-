<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-rose-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-rose-800 focus:bg-rose-800 active:bg-rose-900 focus:outline-none focus:ring-2 focus:ring-rose-700 focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
