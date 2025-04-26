@props(['selected' => false, 'value' => ''])

<option value="{{ $value }}" {{ $selected ? 'selected=selected' : '' }}>
	{{ $slot }}
</option>
