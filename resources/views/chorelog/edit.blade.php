@props(['chorelog' => null, 'users' => null, 'chores' => null])
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Completion: ') . $chorelog->id}}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 text-gray-900 dark:text-gray-100">

                    <form method="POST" action="/chorelog/{{ $chorelog->id }}" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')

                        <x-form.select-input name="user_id" :label-content="'User:'">
                            @if ($users && count($users) > 0)
                                @foreach($users as $user)
                                    <x-form.select-input-option
                                        :selected="$user->id == $chorelog->user_id"
                                        value="{{ $user->id }}"
                                    >{{ $user->name }}</x-form.select-input-option>
                                @endforeach
                            @else
                                <x-form.select-input-option>NA</x-form.select-input-option>
                            @endif
                        </x-form.select-input>

                        <x-form.select-input name="chore_id" :label-content="'Chore:'">
                            @if ($chores && count($chores) > 0)
                                @foreach($chores as $chore)
                                    <x-form.select-input-option
                                        :selected="$chore->id == $chorelog->chore_id"
                                        value="{{ $chore->id }}"
                                    >{{ $chore->name }}</x-form.select-input-option>
                                @endforeach
                            @else
                                <x-form.select-input-option>NA</x-form.select-input-option>
                            @endif
                        </x-form.select-input>

                        <x-form.input
                            name='completed_time'
                            :default="$chorelog->completed_at?->format('Y-m-d\TH:i')"
                            :type="'datetime-local'"
                        >
                            {{ __('Completed Time:') }}
                        </x-form.input>

                        <div>
                            <x-buttons.primary-button type="submit" class="mt-5">
                                {{ __('Update') }}
                            </x-buttons.primary-button>

                            <x-modal.button name="deletechorelog">
                                {{ __('Delete') }}
                            </x-modal.button>
                        </div>
                    </form>

                    <x-modal.popup name="deletechorelog">
                        <form method="POST" action="/chorelog/{{ $chorelog->id }}" class="p-6">
                            @csrf
                            @method('DELETE')
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Are you sure you want to this completion?
                            </h2>

                            <div class="mt-6 flex justify-end">
                                <button type="button"
                                        class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150"
                                        x-on:click="$dispatch('close')">
                                    Cancel
                                </button>

                                <button type="submit"
                                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 ml-3">
                                    Delete Completion
                                </button>
                            </div>
                        </form>
                    </x-modal.popup>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
