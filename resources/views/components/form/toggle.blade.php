@props(['name', 'checked' => false, 'onLabel' => 'On', 'offLabel' => 'Off'])

{{-- On/off switch that always submits 0 or 1; the slot is optional help text shown underneath --}}
<div {{ $attributes->merge(['class' => 'mb-4']) }}>
    <input type="hidden" name="{{ $name }}" value="0">
    <label for="{{ $name }}" class="inline-flex items-center cursor-pointer gap-3">
        <input id="{{ $name }}" type="checkbox" name="{{ $name }}" value="1" class="sr-only peer" @checked(old($name, $checked))>
        <span class="relative w-14 h-8 bg-gray-300 dark:bg-gray-700 rounded-full peer-focus:ring-2 peer-focus:ring-indigo-500 peer-checked:bg-indigo-500 after:content-[''] after:absolute after:top-1 after:start-1 after:bg-white after:rounded-full after:h-6 after:w-6 after:shadow after:transition-all peer-checked:after:translate-x-6"></span>
        <span class="font-medium text-gray-700 dark:text-gray-300 peer-checked:hidden">{{ $offLabel }}</span>
        <span class="font-medium text-gray-700 dark:text-gray-300 hidden peer-checked:inline">{{ $onLabel }}</span>
    </label>
    @if ($slot->isNotEmpty())
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $slot }}</p>
    @endif
</div>
