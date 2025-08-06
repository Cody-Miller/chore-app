@props(['users' => ''])
<x-modal.popup name="complete_chore">
    <form {{ $attributes->merge(['class' => 'p-6', 'method' => 'POST']) }}>
        @csrf
        @method('POST')
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            Are you sure you want to Complete this chore?
        </h2>

        <div x-data="{ showSelect: false }">
            <div class="mt-6 flex justify-between">
                <div class="flex justify-start">
                    <x-buttons.tertiary-button
                        x-on:click="showSelect = !showSelect"
                        class="px-4 py-2 mx-2"
                        type="button"
                    >
                        Complete For Other
                    </x-buttons.tertiary-button>
                </div>

                <div class="flex justify-end flex-wrap gap-y-2">
                    <x-buttons.secondary-button
                        class="px-4 py-2 mx-2 flex-1"
                        x-on:click="$dispatch('close')"
                    >
                        Cancel
                    </x-buttons.secondary-button>

                    <x-buttons.primary-button
                        class="px-4 py-2 mx-2 flex-1"
                        type="submit"
                        autofocus
                    >
                        Complete Chore
                    </x-buttons.primary-button>
                </div>

            </div>
            <div x-show="showSelect">
                <x-form.select-input name="user_id" :label-content="'Complete for user:'">
                    <x-form.select-input-option
                        disabled
                        selected
                        value=""
                    >
                        {{ __('Please Select') }}
                    </x-form.select-input-option>
                    @if ($users && count($users) > 0)
                        @foreach($users as $user)
                            @if($user->id != auth()->user()->id)
                                <x-form.select-input-option
                                    value="{{ $user->id }}">{{ $user->name }}
                                </x-form.select-input-option>
                            @endif
                        @endforeach
                    @else
                        <x-form.select-input-option>NA</x-form.select-input-option>
                    @endif
                </x-form.select-input>
            </div>
        </div>
    </form>
</x-modal.popup>
