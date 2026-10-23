<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('API Tokens') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Manage API tokens for external integrations (Home Assistant, iOS Shortcuts, etc.)') }}
        </p>
    </header>

    @if (session('api_token'))
        <div class="mt-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
            <p class="text-sm font-semibold text-green-800 dark:text-green-200 mb-2">
                {{ __('Token Created: :name', ['name' => session('token_name')]) }}
            </p>
            <p class="text-xs text-green-700 dark:text-green-300 mb-2">
                {{ __('Copy this token now. For security, it won\'t be shown again.') }}
            </p>
            <div class="flex items-center gap-2">
                <code class="flex-1 px-3 py-2 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded text-xs font-mono break-all">
                    {{ session('api_token') }}
                </code>
                <button
                    type="button"
                    onclick="navigator.clipboard.writeText('{{ session('api_token') }}'); this.textContent='Copied!'; setTimeout(() => this.textContent='Copy', 2000)"
                    class="px-3 py-2 bg-green-600 text-white text-xs rounded hover:bg-green-700 whitespace-nowrap"
                >
                    {{ __('Copy') }}
                </button>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('api-tokens.store') }}" class="mt-6 space-y-6">
        @csrf

        <div>
            <x-form.input-label for="token_name" :value="__('Token Name')" />
            <x-form.text-input
                id="token_name"
                name="token_name"
                type="text"
                class="mt-1 block w-full"
                placeholder="e.g., Home Assistant, iOS Shortcuts"
                required
            />
            <x-form.input-error class="mt-2" :messages="$errors->get('token_name')" />
        </div>

        <div>
            <x-form.input-label for="token_timezone" :value="__('Timezone')" />
            <x-form.timezone-select id="token_timezone" name="timezone" class="mt-1 block w-full" :value="old('timezone', auth()->user()->timezone)" />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ __('The API shows and reads times in this timezone, for example where your voice assistant is.') }}
            </p>
            <x-form.input-error class="mt-2" :messages="$errors->get('timezone')" />
        </div>

        <div class="flex items-center gap-4">
            <x-buttons.primary-button type="submit">{{ __('Generate New Token') }}</x-buttons.primary-button>
        </div>
    </form>

    @if ($tokens->isNotEmpty())
        <div class="mt-6">
            <h3 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-3">
                {{ __('Active Tokens') }}
            </h3>

            <div class="space-y-2">
                @foreach ($tokens as $token)
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $token->name }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Created {{ $token->created_at->diffForHumans() }}
                                @if ($token->last_used_at)
                                    • Last used {{ $token->last_used_at->diffForHumans() }}
                                @else
                                    • Never used
                                @endif
                            </p>
                            <form method="POST" action="{{ route('api-tokens.update', $token->id) }}" class="mt-2 flex flex-wrap items-center gap-2">
                                @csrf
                                @method('PATCH')
                                <x-form.timezone-select name="timezone" :value="$token->timezone" class="text-xs py-1 max-w-full" aria-label="{{ __('Timezone for :name', ['name' => $token->name]) }}" />
                                <x-buttons.secondary-button type="submit">{{ __('Save') }}</x-buttons.secondary-button>
                                @if (session('status') === 'api-token-updated' && session('token_id') == $token->id)
                                    <span class="text-xs text-gray-600 dark:text-gray-400">{{ __('Saved.') }}</span>
                                @endif
                            </form>
                        </div>

                        <form method="POST" action="{{ route('api-tokens.destroy', $token->id) }}">
                            @csrf
                            @method('DELETE')

                            <x-buttons.danger-button
                                type="submit"
                                onclick="return confirm('Are you sure you want to revoke this token?')"
                            >
                                {{ __('Revoke') }}
                            </x-buttons.danger-button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</section>
