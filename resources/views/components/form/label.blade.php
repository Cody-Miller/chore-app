@props(['name'])
<label class="block mb-2 uppercase font-bold text-xs text-gray-700 dark:text-gray-300" for="{{ $name }}">
	{{ $slot }}
</label>
