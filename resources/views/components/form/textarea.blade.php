@props(['name'])
<x-form.field>
	<x-form.label name="{{ $name }}" />
    <textarea
        name="{{ $name }}"
        cols="25"
        rows="4"
        class="w-full text-sm focus:outline-none focus:ring p-2 border-gray-400 rounded"
        placeholder="Please add text"
        required
    >{{ $slot ?? old($name) }}</textarea>
    <x-form.error name="{{ $name }}" />
</x-form.field>