@props(['tabNumber'])
<button
    x-on:click="openTab = {{ $tabNumber }}"
    :class="{ 'dark:hover:bg-indigo-500 dark:bg-indigo-500 text-white': openTab === {{ $tabNumber }} }"
    {{ $attributes->merge(['class' => 'flex-1 py-2 px-4 sm:rounded-lg focus:outline-none focus:shadow-outline-blue dark:hover:bg-indigo-400 transition-all duration-300'])}}
>
    {{ $slot }}
</button>
