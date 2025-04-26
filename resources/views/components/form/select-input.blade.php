@props(['name' => null, 'labelContent' => '', 'disabled' => false])
<x-form.field>
    <x-input-label for="{{ $name }}">
		{{ $labelContent }}
	</x-input-label>
    <select name="{{ $name }}" {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'w-full rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-500 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-500 dark:focus:ring-offset-gray-800']) !!}>
        {{ $slot }}
    </select>
    <x-form.error name="{{ $name }}" />
</x-form.field>
