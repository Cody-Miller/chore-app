@props(['active'])

@php
$classes =  'inline-flex
            items-center
            px-3
            py-1
            rounded-lg
            text-sm
            font-medium
            transition
            duration-150
            ease-in-out
            focus:outline-none';
if ($active ?? false) {
    // Active
    $classes .= ' bg-indigo-500
                text-white';
} else {
    // Inactive
    $classes .= ' text-slate-700
                hover:bg-thunder-300
                dark:text-gray-300
                dark:hover:bg-indigo-400
                dark:hover:text-white';
}
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
