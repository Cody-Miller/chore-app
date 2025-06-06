@props(['name' => null, 'slug' => null])
<button
    type="submit"
    {{ $attributes->merge(['class' => 'inline-flex items-center px-4 py-2 mt-1 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150']) }}
    x-data=""
    x-on:click.prevent="$dispatch('open-modal', ['{{ $name }}', '{{ $slug }}'])"
>
    {{ $slot }}
</button>
