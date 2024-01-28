@props(['defaultTabNumber'])
<div
	x-data="{ openTab: {{ $defaultTabNumber }} }"
	{{ $attributes->merge(['class' => ''])}}
>
	{{ $slot }}
</div>