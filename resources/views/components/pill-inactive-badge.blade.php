@props(['pill'])
@unless($pill->is_active)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300 align-middle']) }}>
        Inactive
    </span>
@endunless
