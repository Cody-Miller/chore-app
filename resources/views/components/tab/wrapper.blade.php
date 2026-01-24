@props(['defaultTabNumber'])
<div
	x-data="{ openTab: parseInt(new URLSearchParams(window.location.search).get('tab')) || {{ $defaultTabNumber }} }"
	x-init="$watch('openTab', value => $dispatch('tab-changed', value))"
	{{ $attributes->merge(['class' => ''])}}
>
	{{ $slot }}
</div>