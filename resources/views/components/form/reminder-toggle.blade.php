@props(['checked' => false])

{{-- Opt-in switch for ntfy reminders on a chore or pill; the slot says when the reminder is sent --}}
<x-form.toggle name="notifications_enabled" :checked="$checked" :on-label="__('Reminders on')" :off-label="__('Reminders off')" {{ $attributes }}>
    {{ $slot }}
    @unless (auth()->user()->ntfy_topic)
        {{ __('Set your ntfy topic on your') }}
        <a href="{{ route('profile.edit') }}#notifications" class="underline hover:text-gray-700 dark:hover:text-gray-200">{{ __('Profile') }}</a>
        {{ __('to receive these.') }}
    @endunless
</x-form.toggle>
