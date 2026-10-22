@props(['name' => null, 'slug' => null, 'data' => null, 'variant' => 'danger'])
@php
$variantClasses = [
    'danger' => 'bg-red-600 hover:bg-red-500 active:bg-red-700 focus:ring-red-500',
    'primary' => 'bg-blue-600 hover:bg-blue-500 focus:ring-blue-600',
][$variant];
@endphp
<button
    x-data=""
    x-on:click.prevent="$dispatch('open-modal', ['{{ $name }}', '{{ $slug }}', {{ $data ?? 'null' }}])"
    {{
        $attributes->merge([
            'type' => 'button',
            'class' => 'inline-flex items-center justify-center px-4 py-2 ' . $variantClasses . ' border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150'
        ])
    }}
>
    {{ $slot }}
</button>
