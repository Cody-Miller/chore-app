<section id="notifications">
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Notifications') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Reminders are pushed to the ntfy app on your phone. Subscribe to your topic in the app, then turn on reminders for each chore or medication you want.') }}
        </p>
    </header>

    @if (session('ntfy_error'))
        <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-800 dark:text-red-200 break-words">
            {{ session('ntfy_error') }}
        </div>
    @endif

    <form method="post" action="{{ route('notifications.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-form.input-label for="ntfy_topic" :value="__('ntfy Topic')" />
            <x-form.text-input
                id="ntfy_topic"
                name="ntfy_topic"
                type="text"
                class="mt-1 block w-full"
                :value="old('ntfy_topic', $user->ntfy_topic)"
                placeholder="bechore-{{ Str::slug($user->name) }}"
                maxlength="64"
            />
            <x-form.input-error class="mt-2" :messages="$errors->get('ntfy_topic')" />
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                {{ __('Leave empty to stop receiving reminders.') }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <x-buttons.primary-button type="submit">
                {{ __('Save') }}
            </x-buttons.primary-button>

            @if ($user->ntfy_topic)
                <x-buttons.secondary-button type="submit" form="send-test-notification">
                    {{ __('Send Test Notification') }}
                </x-buttons.secondary-button>
            @endif

            @if (session('status') === 'notifications-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >{{ __('Saved.') }}</p>
            @elseif (session('status') === 'notification-test-sent')
                <p class="text-sm text-green-600 dark:text-green-400">{{ __('Test sent. Check your phone.') }}</p>
            @endif
        </div>
    </form>

    <form id="send-test-notification" method="post" action="{{ route('notifications.test') }}">
        @csrf
    </form>
</section>
