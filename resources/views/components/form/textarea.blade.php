@props(['name', 'default' => '', 'placeholder' => '', 'labelContent' => null])
<x-form.field>
    @if($labelContent)
        <x-form.label :name="$name">{{ $labelContent }}</x-form.label>
    @endif
    <textarea
        name="{{ $name }}"
        cols="25"
        rows="4"
        class="w-full rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-gray-900 dark:text-gray-300 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-500 dark:focus:ring-offset-gray-800 p-2"
        placeholder="{{ $placeholder ?: ($slot->isNotEmpty() ? $slot : 'Please add text') }}"
        {{ $attributes }}
    >{{ old($name, $default) }}</textarea>
    <x-form.error name="{{ $name }}" />
</x-form.field>