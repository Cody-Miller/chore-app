@props(['tag'])
<a
    href="{{ $tag->filterUrl() }}"
    style="background-color: {{ $tag->color }}"
    {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium text-gray-800 hover:brightness-95 hover:underline']) }}
>
    {{ $tag->name }}
</a>
