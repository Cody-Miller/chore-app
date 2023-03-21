@props(['name', 'default' => ''])
<x-form.field>
	<x-input-label for="{{ $name }}">
		{{ $slot }}
	</x-input-label>
	<input class="w-full rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
		name="{{ $name }}"
		id="{{ $name }}"
		{{ $attributes(['value' => old($name, $default)]) }}
	>
	<x-form.error name="{{ $name }}" />
</x-form.field>