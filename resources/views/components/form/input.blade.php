@props(['name' => null, 'default' => '', 'type' => 'text'])
<x-form.field>
    <x-form.input-label for="{{ $name }}">
        {{ $slot }}
    </x-form.input-label>
    @if($type == 'file')
        <input class="w-full text-sm text-gray-900 dark:text-gray-300 border border-gray-300 dark:border-gray-700 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-900 focus:outline-none file:mr-4 file:py-2 file:px-4 file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-gray-800 dark:file:text-indigo-400 dark:hover:file:bg-gray-700"
               name="{{ $name }}"
               id="{{ $name }}"
               type="{{$type}}"
                {{ $attributes }}
        >
    @else
        <input class="w-full rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-500 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-500 dark:focus:ring-offset-gray-800"
               name="{{ $name }}"
               id="{{ $name }}"
               type="{{$type}}"
               @if($type == "'datetime-local'")
                   step="60"
               @endif
                {{ $attributes(['value' => old($name, $default)]) }}
        >
    @endif
    <x-form.error name="{{ $name }}" />
</x-form.field>
