@props(['name' => false, 'tabNumber'])
<div
    x-show="openTab === {{ $tabNumber }}"
    {{ $attributes->merge(['class' => 'transition-all duration-300 bg-white dark:bg-gray-700 p-4 sm:rounded-lg shadow-md border-l-4 border-indigo-500'])}}
>
    @if ($name)
        <h2 class="font-semibold mb-2 text-indigo-400">
            {{ $name }}
        </h2>
    @endif
    <p class="text-gray-700 dark:text-gray-100">
        {{ $slot }}
    </p>
</div>
