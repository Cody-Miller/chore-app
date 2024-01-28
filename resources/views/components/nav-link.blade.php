@props(['active'])

@php
$classes =  'inline-flex
            items-center
            px-1
            pt-1
            border-b-2
            text-sm
            font-medium
            leading-5
            transition
            duration-150
            ease-in-out
            focus:outline-none
            dark:focus:outline-none';
if ($active ?? false) {
    // Active
    $classes .= 'border-indigo-400
                text-slate-900
                focus:border-indigo-700
                dark:text-indigo-600
                dark:border-indigo-600
                dark:focus:text-indigo-400
                dark:focus:border-indigo-400';
} else {
    // Inactive
    $classes .= 'border-transparent
                text-slate-300
                hover:text-slate-700
                hover:border-indigo-400
                focus:text-slate-700
                focus:border-gray-300
                dark:border-transparent
                dark:text-gray-300
                dark:hover:text-indigo-400
                dark:focus:text-indigo-400
                dark:hover:border-indigo-400
                dark:focus:border-indigo-400';
}
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
