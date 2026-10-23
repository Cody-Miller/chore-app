@props(['name' => 'timezone', 'value' => null])
@php
    // Common US timezones first, then every timezone
    $common = [
        'America/New_York' => 'Eastern',
        'America/Chicago' => 'Central',
        'America/Denver' => 'Mountain',
        'America/Phoenix' => 'Arizona',
        'America/Los_Angeles' => 'Pacific',
        'America/Anchorage' => 'Alaska',
        'Pacific/Honolulu' => 'Hawaii',
    ];
    $value = $value ?: \App\Support\Timezone::DEFAULT;
@endphp
<select name="{{ $name }}" {{ $attributes->merge(['class' => 'border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-indigo-500 dark:focus:ring-indigo-500 rounded-md shadow-sm']) }}>
    <optgroup label="{{ __('Common') }}">
        @foreach ($common as $zone => $label)
            <option value="{{ $zone }}" @selected($zone === $value)>{{ $label }} ({{ str_replace('_', ' ', $zone) }})</option>
        @endforeach
    </optgroup>
    <optgroup label="{{ __('All timezones') }}">
        @foreach (DateTimeZone::listIdentifiers() as $zone)
            <option value="{{ $zone }}" @selected($zone === $value && !isset($common[$zone]))>{{ str_replace('_', ' ', $zone) }}</option>
        @endforeach
    </optgroup>
</select>
