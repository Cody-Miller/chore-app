@props(['name' => null, 'default' => '', 'type' => 'text'])
<x-form.field>
    <x-form.input-label for="{{ $name }}">
        {{ $slot }}
    </x-form.input-label>
    <input class="w-full rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-500 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-500 dark:focus:ring-offset-gray-800"
           name="{{ $name }}"
           id="{{ $name }}"
           type="{{$type}}"
           @if($type == "'datetime-local'")
               step="60"
           @endif
            {{ $attributes(['value' => old($name, $default)]) }}
    >
    <x-form.error name="{{ $name }}" />
</x-form.field>
